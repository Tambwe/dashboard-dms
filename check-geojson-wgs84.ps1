param(
    [Parameter(Mandatory = $true)]
    [string]$Path,

    [int]$MaxInvalidSamples = 20
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if (-not (Test-Path -LiteralPath $Path)) {
    Write-Error "Fichier introuvable: $Path"
    exit 2
}

try {
    $raw = Get-Content -LiteralPath $Path -Raw -Encoding UTF8
} catch {
    Write-Error "Lecture impossible du fichier: $Path"
    exit 2
}

try {
    $geo = $raw | ConvertFrom-Json
} catch {
    Write-Error "JSON invalide: $($_.Exception.Message)"
    exit 2
}

if ($geo.type -ne 'FeatureCollection' -and $geo.type -ne 'Feature' -and -not $geo.geometry) {
    Write-Warning "Structure GeoJSON non standard (type: $($geo.type))."
}

$stats = [ordered]@{
    TotalCoordinatePairs = 0
    InvalidLonLatPairs = 0
    MinLon = [double]::PositiveInfinity
    MaxLon = [double]::NegativeInfinity
    MinLat = [double]::PositiveInfinity
    MaxLat = [double]::NegativeInfinity
    MinAbsLon = [double]::PositiveInfinity
    MaxAbsLon = 0.0
    MinAbsLat = [double]::PositiveInfinity
    MaxAbsLat = 0.0
}

$invalidSamples = New-Object System.Collections.Generic.List[string]

function Test-Number([object]$v) {
    if ($null -eq $v) { return $false }
    return ($v -is [byte] -or $v -is [int16] -or $v -is [int32] -or $v -is [int64] -or $v -is [single] -or $v -is [double] -or $v -is [decimal])
}

function Walk-Coordinates {
    param(
        [Parameter(Mandatory = $true)]
        [object]$Node,
        [string]$PathLabel = '$'
    )

    if ($null -eq $Node) { return }

    if ($Node -is [System.Collections.IList]) {
        if ($Node.Count -ge 2 -and (Test-Number $Node[0]) -and (Test-Number $Node[1]) -and -not ($Node[0] -is [System.Collections.IList])) {
            $lon = [double]$Node[0]
            $lat = [double]$Node[1]

            $stats.TotalCoordinatePairs++

            if ($lon -lt $stats.MinLon) { $stats.MinLon = $lon }
            if ($lon -gt $stats.MaxLon) { $stats.MaxLon = $lon }
            if ($lat -lt $stats.MinLat) { $stats.MinLat = $lat }
            if ($lat -gt $stats.MaxLat) { $stats.MaxLat = $lat }

            $absLon = [math]::Abs($lon)
            $absLat = [math]::Abs($lat)
            if ($absLon -lt $stats.MinAbsLon) { $stats.MinAbsLon = $absLon }
            if ($absLon -gt $stats.MaxAbsLon) { $stats.MaxAbsLon = $absLon }
            if ($absLat -lt $stats.MinAbsLat) { $stats.MinAbsLat = $absLat }
            if ($absLat -gt $stats.MaxAbsLat) { $stats.MaxAbsLat = $absLat }

            $isValidLonLat = ($absLon -le 180.0) -and ($absLat -le 90.0)
            if (-not $isValidLonLat) {
                $stats.InvalidLonLatPairs++
                if ($invalidSamples.Count -lt $MaxInvalidSamples) {
                    [void]$invalidSamples.Add("$PathLabel -> [$lon, $lat]")
                }
            }

            return
        }

        for ($i = 0; $i -lt $Node.Count; $i++) {
            Walk-Coordinates -Node $Node[$i] -PathLabel "$PathLabel[$i]"
        }
    }
}

function Walk-Geometry {
    param(
        [object]$Geometry,
        [string]$PathLabel = '$.geometry'
    )

    if ($null -eq $Geometry) { return }
    if (-not $Geometry.PSObject.Properties.Name.Contains('type')) { return }

    $gType = [string]$Geometry.type

    if ($gType -eq 'GeometryCollection') {
        $geometries = $Geometry.geometries
        if ($geometries -is [System.Collections.IList]) {
            for ($i = 0; $i -lt $geometries.Count; $i++) {
                Walk-Geometry -Geometry $geometries[$i] -PathLabel "$PathLabel.geometries[$i]"
            }
        }
        return
    }

    if ($Geometry.PSObject.Properties.Name.Contains('coordinates')) {
        Walk-Coordinates -Node $Geometry.coordinates -PathLabel "$PathLabel.coordinates"
    }
}

if ($geo.type -eq 'FeatureCollection' -and $geo.features -is [System.Collections.IList]) {
    for ($i = 0; $i -lt $geo.features.Count; $i++) {
        Walk-Geometry -Geometry $geo.features[$i].geometry -PathLabel "$.features[$i].geometry"
    }
} elseif ($geo.type -eq 'Feature') {
    Walk-Geometry -Geometry $geo.geometry -PathLabel '$.geometry'
} else {
    Walk-Geometry -Geometry $geo -PathLabel '$'
}

Write-Output "--- Validation GeoJSON WGS84 ---"
Write-Output "Fichier                : $Path"
Write-Output "Paires de coordonnees  : $($stats.TotalCoordinatePairs)"
Write-Output "Paires invalides       : $($stats.InvalidLonLatPairs)"

if ($stats.TotalCoordinatePairs -gt 0) {
    Write-Output "Etendue lon            : [$($stats.MinLon); $($stats.MaxLon)]"
    Write-Output "Etendue lat            : [$($stats.MinLat); $($stats.MaxLat)]"
}

$crsName = $null
if ($geo.PSObject.Properties.Name.Contains('crs')) {
    try {
        $crsName = $geo.crs.properties.name
    } catch {
        $crsName = $null
    }
}
if ($crsName) {
    Write-Output "CRS declare            : $crsName"
}

if ($stats.InvalidLonLatPairs -gt 0) {
    Write-Output ""
    Write-Output "ECHANTILLON des coordonnees hors bornes lon/lat:"
    foreach ($sample in $invalidSamples) {
        Write-Output "- $sample"
    }

    if ($stats.MaxAbsLon -gt 180 -or $stats.MaxAbsLat -gt 90) {
        Write-Output ""
        Write-Warning "Ce GeoJSON ne semble pas en EPSG:4326 (WGS84). Reprojection necessaire avant affichage web."
    }

    exit 1
}

Write-Output ""
Write-Output "OK: toutes les coordonnees respectent lon/lat (|lon| <= 180 et |lat| <= 90)."
exit 0
