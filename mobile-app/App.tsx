import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import * as SQLite from 'expo-sqlite';
import * as ImagePicker from 'expo-image-picker';
import * as Location from 'expo-location';
import { StatusBar } from 'expo-status-bar';
import MapView, { Marker, Polygon, Polyline, Region } from 'react-native-maps';
import { SafeAreaView } from 'react-native-safe-area-context';
import {
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  useWindowDimensions,
  View,
  Image,
} from 'react-native';

const DEFAULT_API_BASE = 'http://192.168.16.108:8080';
const db = SQLite.openDatabaseSync('dms_mobile.db');

type TabKey = 'dashboard' | 'sector' | 'campaign' | 'collecte_form' | 'geography' | 'movement' | 'ready_sync' | 'ossat';
type SectorKey = 'wash' | 'sante' | 'protection' | 'education' | 'environnement' | 'abri';
type MovementType = 'arrivee' | 'depart' | 'recensement' | 'ajustement';
type FormStatus = 'draft' | 'pending' | 'synced' | 'correction';
type ReadySyncOrigin = 'dashboard' | 'collecte' | 'geography' | 'movement';
type SyncErrorDetail = {
  field: string;
  label: string;
  available: number | null;
  movement: number | null;
  projected: number | null;
  message: string;
};
const MOVEMENT_TYPE_OPTIONS: Array<{ value: MovementType; label: string }> = [
  { value: 'arrivee', label: 'Arrivée' },
  { value: 'depart', label: 'Départ' },
  { value: 'recensement', label: 'Recensement' },
  { value: 'ajustement', label: 'Ajustement' },
];
type Point = { latitude: number; longitude: number };
type FormRecord = {
  id: string;
  type: 'sector' | 'geography' | 'ossat' | 'questionnaire' | 'movement';
  site_id: number;
  campaign_id?: number | null;
  sector?: SectorKey;
  payload: Record<string, any>;
  created_at: string;
  status: FormStatus;
  sync_error?: string | null;
  sync_error_details?: SyncErrorDetail[];
};
type User = { id: number; name: string; email: string };
type QuestionnaireQuestion = {
  type: string;
  name: string;
  label?: string;
  label_fr?: string;
  label_en?: string;
  required?: string;
  list_name?: string | null;
  listName?: string | null;
  choice_filter?: string;
};
type QuestionnaireChoice = {
  list_name?: string;
  listName?: string;
  name: string;
  label?: string;
  label_fr?: string;
  label_en?: string;
  province?: string;
  territoire?: string;
  zs?: string;
};
type QuestionnaireDefinition = {
  id: number;
  code: string;
  title: string;
  version: number;
  survey: QuestionnaireQuestion[];
  choices: QuestionnaireChoice[];
};
type ProvinceRef = { id: number; name: string };
type TerritoireRef = { id: number; name: string; province_id?: number | null };
type CommuneRef = { id: number; name: string; territoire_id?: number | null; province_id?: number | null };
type SiteRef = {
  id: number;
  nom?: string;
  code_site?: string;
  province?: string;
  territoire?: string;
  zone_sante?: string;
  commune_id?: number | null;
  latitude?: number | null;
  longitude?: number | null;
  geometry_type?: 'point' | 'polygon' | string | null;
  geojson_data?: Record<string, any> | string | null;
};
type MovementReasonRef = {
  id: number;
  name: string;
  code?: string | null;
  category_name?: string | null;
  category_code?: string | null;
};
type SiteSelectionMode = 'existing' | 'new';
type NewSiteFormData = Record<string, string>;
type GpsPointCategory =
  | 'robinet'
  | 'douche'
  | 'toilette'
  | 'abris'
  | 'point_eau'
  | 'centre_sante'
  | 'ecole'
  | 'universite'
  | 'marche'
  | 'hopital'
  | 'lavage_main'
  | 'autre';
type GpsPolygonCategory = 'contour_site' | 'bloc';
type PicklistOption = { value: string; label: string };
type QuestionnaireSubgroup = { key: string; label: string };
type QuestionnaireSection = { key: string; label: string; children: QuestionnaireSubgroup[] };
type QuestionnaireRenderedQuestion = QuestionnaireQuestion & { subgroup_key: string | null; subgroup_label: string | null };
type CollectionCampaign = {
  id: number;
  user_id: number;
  site_id: number;
  site_label: string;
  period_mm_yyyy: string;
  is_archived: boolean;
  created_at: string;
  updated_at: string;
};
type CampaignStatus = 'brouillon' | 'en_attente' | 'synchronise';
type CampaignOverview = CollectionCampaign & {
  pending_count: number;
  synced_count: number;
  total_forms: number;
  status: CampaignStatus;
};
type CampaignTypeFilter = 'all' | 'questionnaire' | 'sector' | 'geography' | 'ossat';
type CampaignSyncFilter = 'all' | 'pending' | 'synced';
type CachedReferencePayload = {
  provinces: ProvinceRef[];
  territoires: TerritoireRef[];
  communes: CommuneRef[];
  sites: SiteRef[];
  movement_reasons: MovementReasonRef[];
};

const MOVEMENT_DEMOGRAPHIC_FIELDS = [
  { key: 'f_0_5', label: 'Femmes 0-5 ans' },
  { key: 'f_6_17', label: 'Femmes 6-17 ans' },
  { key: 'f_18_59', label: 'Femmes 18-59 ans' },
  { key: 'f_60_plus', label: 'Femmes 60 ans et +' },
  { key: 'h_0_5', label: 'Hommes 0-5 ans' },
  { key: 'h_6_17', label: 'Hommes 6-17 ans' },
  { key: 'h_18_59', label: 'Hommes 18-59 ans' },
  { key: 'h_60_plus', label: 'Hommes 60 ans et +' },
] as const;
const MOVEMENT_POPULATION_FIELDS = [
  'menages',
  'individus',
  ...MOVEMENT_DEMOGRAPHIC_FIELDS.map((field) => field.key),
] as const;

function movementPopulationFieldLabel(field: string): string {
  if (field === 'menages') {
    return 'Ménages';
  }
  if (field === 'individus') {
    return 'Individus';
  }
  return MOVEMENT_DEMOGRAPHIC_FIELDS.find((item) => item.key === field)?.label ?? field;
}

function nullableFiniteNumber(value: unknown): number | null {
  if (value === null || value === undefined || value === '') {
    return null;
  }
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : null;
}

function parseSyncErrorDetails(rawDetails: unknown, fallbackMessage = ''): SyncErrorDetail[] {
  if (Array.isArray(rawDetails)) {
    const details = rawDetails
      .filter((detail) => detail && typeof detail === 'object')
      .map((detail: any): SyncErrorDetail => ({
        field: String(detail.field ?? ''),
        label: String(detail.label ?? movementPopulationFieldLabel(String(detail.field ?? ''))),
        available: nullableFiniteNumber(detail.available),
        movement: nullableFiniteNumber(detail.movement),
        projected: nullableFiniteNumber(detail.projected),
        message: String(detail.message ?? fallbackMessage),
      }));
    if (details.length > 0) {
      return details;
    }
  }

  const match = fallbackMessage.match(
    /:\s*(.+?) disponible = (-?\d+), mouvement = (-?\d+), solde projeté = (-?\d+)/i,
  );
  if (!match) {
    return [];
  }

  return [{
    field: '',
    label: match[1].trim(),
    available: Number(match[2]),
    movement: Number(match[3]),
    projected: Number(match[4]),
    message: fallbackMessage,
  }];
}

class SyncRequestError extends Error {
  details: SyncErrorDetail[];

  constructor(message: string, details: SyncErrorDetail[]) {
    super(message);
    this.name = 'SyncRequestError';
    this.details = details;
  }
}

const INITIAL_MOVEMENT_DATA: Record<string, string> = {
  date_mouvement: formatDate(new Date()),
  type_mouvement: 'arrivee',
  raison_mouvement_id: '',
  periode: '',
  menages: '0',
  individus: '0',
  f_0_5: '0',
  f_6_17: '0',
  f_18_59: '0',
  f_60_plus: '0',
  h_0_5: '0',
  h_6_17: '0',
  h_18_59: '0',
  h_60_plus: '0',
  raison: '',
  description: '',
  source: 'application_mobile',
  round: '',
};

type SyncConformity = {
  valid: boolean;
  errors: string[];
  warning?: string;
};

function checkSyncConformity(record: FormRecord): SyncConformity {
  const errors: string[] = [];
  const siteId = Number(record.payload?.site_id ?? record.site_id ?? 0);
  const isNewSite = Boolean(record.payload?.is_new_site);
  if ((!siteId || Number.isNaN(siteId)) && !isNewSite) {
    errors.push('Site manquant.');
  }

  if (record.type !== 'movement') {
    return { valid: errors.length === 0, errors };
  }

  const movementType = String(record.payload?.type_mouvement ?? '') as MovementType;
  if (!['arrivee', 'depart', 'recensement', 'ajustement'].includes(movementType)) {
    errors.push('Type de mouvement invalide.');
  }
  if (!isValidCollecteDate(String(record.payload?.date_mouvement ?? ''))) {
    errors.push('Date du mouvement invalide.');
  }

  const values: Record<string, number> = {};
  for (const field of MOVEMENT_POPULATION_FIELDS) {
    const rawValue = record.payload?.[field];
    const value = typeof rawValue === 'number' ? rawValue : Number(rawValue);
    if (!Number.isInteger(value)) {
      errors.push(`Effectif ${field} invalide.`);
      continue;
    }
    values[field] = value;
  }

  if (errors.length === 0) {
    const demographicTotal = MOVEMENT_DEMOGRAPHIC_FIELDS.reduce(
      (total, field) => total + values[field.key],
      0,
    );
    if (values.individus !== demographicTotal) {
      errors.push('Le total individus ne correspond pas aux groupes démographiques.');
    }

    if (movementType === 'arrivee' || movementType === 'recensement') {
      if (MOVEMENT_POPULATION_FIELDS.some((field) => values[field] < 0)) {
        errors.push('Une arrivée ou un recensement ne peut pas contenir de valeur négative.');
      }
    }

    if (movementType === 'depart') {
      const allNonPositive = MOVEMENT_POPULATION_FIELDS.every((field) => values[field] <= 0);
      const allNonNegative = MOVEMENT_POPULATION_FIELDS.every((field) => values[field] >= 0);
      const hasPositiveValue = MOVEMENT_POPULATION_FIELDS.some((field) => values[field] > 0);
      if (!allNonPositive && !allNonNegative) {
        errors.push('Un départ contient un mélange incohérent de valeurs positives et négatives.');
      }
      return {
        valid: errors.length === 0,
        errors,
        warning: allNonNegative && hasPositiveValue && errors.length === 0
          ? 'Les effectifs seront convertis en valeurs négatives avant l’envoi.'
          : undefined,
      };
    }
  }

  return { valid: errors.length === 0, errors };
}

function normalizeRecordForSync(record: FormRecord): FormRecord {
  if (record.type !== 'movement') {
    return record;
  }

  const movementType = record.payload?.type_mouvement as MovementType;
  if (movementType !== 'depart') {
    return record;
  }

  const normalizedPayload = { ...record.payload };
  for (const field of MOVEMENT_POPULATION_FIELDS) {
    normalizedPayload[field] = -Math.abs(Number(record.payload?.[field] ?? 0));
  }
  return { ...record, payload: normalizedPayload };
}

const INITIAL_REGION: Region = {
  latitude: -0.8611,
  longitude: 29.2333,
  latitudeDelta: 0.05,
  longitudeDelta: 0.05,
};

const sectorFieldMap: Record<SectorKey, Array<{ key: string; label: string; type: 'number' | 'text' | 'boolean' }>> = {
  wash: [
    { key: 'wash_disponible', label: 'Disponible', type: 'boolean' },
    { key: 'wash_points_eau', label: 'Points d’eau', type: 'number' },
    { key: 'wash_litres_par_personne', label: 'Litres par personne', type: 'number' },
    { key: 'wash_latrines', label: 'Latrines', type: 'number' },
    { key: 'wash_douches', label: 'Douches', type: 'number' },
    { key: 'wash_gestion_dechets', label: 'Gestion des déchets', type: 'boolean' },
    { key: 'wash_observations', label: 'Observations', type: 'text' },
  ],
  sante: [
    { key: 'sante_disponible', label: 'Disponible', type: 'boolean' },
    { key: 'sante_structures_fonctionnelles', label: 'Structures fonctionnelles', type: 'number' },
    { key: 'sante_personnel_medical', label: 'Personnel médical', type: 'number' },
    { key: 'sante_consultations_mois', label: 'Consultations / mois', type: 'number' },
    { key: 'sante_services_offerts', label: 'Services offerts', type: 'text' },
    { key: 'sante_observations', label: 'Observations', type: 'text' },
  ],
  protection: [
    { key: 'gestion_disponible', label: 'Disponible', type: 'boolean' },
    { key: 'gestion_comite_site', label: 'Comité de site', type: 'boolean' },
    { key: 'gestion_membres_comite', label: 'Membres du comité', type: 'number' },
    { key: 'gestion_mecanisme_plainte', label: 'Mécanisme de plainte', type: 'boolean' },
    { key: 'gestion_reunions_mois', label: 'Réunions / mois', type: 'number' },
    { key: 'gestion_partenaires', label: 'Partenaires', type: 'text' },
    { key: 'gestion_observations', label: 'Observations', type: 'text' },
  ],
  education: [
    { key: 'education_disponible', label: 'Disponible', type: 'boolean' },
    { key: 'education_ecoles_fonctionnelles', label: 'Écoles fonctionnelles', type: 'number' },
    { key: 'education_enseignants', label: 'Enseignants', type: 'number' },
    { key: 'education_eleves_inscrits', label: 'Élèves inscrits', type: 'number' },
    { key: 'education_salles_classe', label: 'Salles de classe', type: 'number' },
    { key: 'education_niveaux_offerts', label: 'Niveaux offerts', type: 'text' },
    { key: 'education_observations', label: 'Observations', type: 'text' },
  ],
  environnement: [
    { key: 'environnement_disponible', label: 'Disponible', type: 'boolean' },
    { key: 'environnement_gestion_dechets', label: 'Gestion des déchets', type: 'boolean' },
    { key: 'environnement_drainage', label: 'Drainage', type: 'boolean' },
    { key: 'environnement_espaces_verts', label: 'Espaces verts', type: 'number' },
    { key: 'environnement_risques', label: 'Risques', type: 'text' },
    { key: 'environnement_observations', label: 'Observations', type: 'text' },
  ],
  abri: [
    { key: 'abri_ame_disponible', label: 'Disponible', type: 'boolean' },
    { key: 'abri_logements_fonctionnels', label: 'Logements fonctionnels', type: 'number' },
    { key: 'abri_types', label: 'Types d’abri', type: 'text' },
    { key: 'abri_menages_ame', label: 'Ménages AME', type: 'number' },
    { key: 'abri_ame_distribues', label: 'AME distribués', type: 'number' },
    { key: 'abri_observations', label: 'Observations', type: 'text' },
  ],
};

const boolOptions = ['Oui', 'Non'];
const GPS_POINT_CATEGORIES: Array<{ value: GpsPointCategory; label: string }> = [
  { value: 'robinet', label: 'Robinet' },
  { value: 'douche', label: 'Douche' },
  { value: 'toilette', label: 'Toilette' },
  { value: 'abris', label: 'Abris' },
  { value: 'point_eau', label: "Point d'eau" },
  { value: 'centre_sante', label: 'Centre de sante' },
  { value: 'ecole', label: 'Ecole' },
  { value: 'universite', label: 'Universite' },
  { value: 'marche', label: 'Marche' },
  { value: 'hopital', label: 'Hopital' },
  { value: 'lavage_main', label: 'Lavage main' },
  { value: 'autre', label: 'Autre' },
];
const GPS_POLYGON_CATEGORIES: Array<{ value: GpsPolygonCategory; label: string }> = [
  { value: 'contour_site', label: 'Contour site' },
  { value: 'bloc', label: 'Bloc' },
];
const TYPE_GESTION_OPTIONS: PicklistOption[] = [
  { value: 'spontane', label: 'Spontané' },
  { value: 'planifie', label: 'Planifié' },
  { value: 'transit', label: 'Transit' },
  { value: 'retour', label: 'Retour' },
  { value: 'relocalisation', label: 'Relocalisation' },
  { value: 'autre', label: 'Autre' },
];
const INITIAL_NEW_SITE_FORM: NewSiteFormData = {
  nom: '',
  code_site: '',
  commune_id: '',
  province: '',
  territoire: '',
  zone_sante: '',
  source: 'mobile',
  type_gestion: '',
};

function formatDate(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function extractQuestionListName(question: QuestionnaireQuestion): string {
  const explicit = String(question.list_name ?? question.listName ?? '').trim().toLowerCase();
  if (explicit) {
    return explicit;
  }

  const type = String(question.type || '').trim();
  const selectMatch = type.match(/^select[_ ](?:one|multiple)\s+(.+)$/i);
  if (selectMatch) {
    return String(selectMatch[1] || '').trim().toLowerCase();
  }

  const fileMatch = type.match(/^select[_ ]one[_ ]from[_ ]file\s+(.+)$/i);
  if (fileMatch) {
    return String(fileMatch[1] || '')
      .trim()
      .replace(/\.csv$/i, '_csv')
      .toLowerCase();
  }

  return '';
}

function getQuestionLabel(question: QuestionnaireQuestion): string {
  return String(question.label ?? question.label_fr ?? question.label_en ?? question.name ?? '').trim();
}

function getChoiceLabel(choice: QuestionnaireChoice): string {
  return String(choice.label ?? choice.label_fr ?? choice.label_en ?? choice.name ?? '').trim();
}

function parseSelectedValues(value: string): string[] {
  return String(value || '')
    .split(' ')
    .map((item) => item.trim())
    .filter((item) => item.length > 0);
}

function isValidPeriod(period: string): boolean {
  return /^(0[1-9]|1[0-2])-\d{4}$/.test(String(period).trim());
}

function normalizeLookupValue(value: unknown): string {
  return String(value ?? '').trim().toLowerCase();
}

function periodToCollecteDate(period: string): string {
  if (!isValidPeriod(period)) {
    return formatDate(new Date());
  }
  const [month, year] = period.split('-');
  return `${year}-${month}-01`;
}

function collecteDateToPeriod(dateValue: string): string {
  const normalized = String(dateValue || '').trim();
  const match = normalized.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) {
    const now = new Date();
    return `${String(now.getMonth() + 1).padStart(2, '0')}-${now.getFullYear()}`;
  }
  return `${match[2]}-${match[1]}`;
}

function isValidCollecteDate(dateValue: string): boolean {
  return /^\d{4}-\d{2}-\d{2}$/.test(String(dateValue || '').trim());
}

function isOtherChoiceLabel(value: string, label: string): boolean {
  const normalizedValue = String(value || '').trim().toLowerCase();
  const normalizedLabel = String(label || '').trim().toLowerCase();
  return (
    normalizedValue.includes('autre') ||
    normalizedValue.includes('other') ||
    normalizedLabel.includes('autre') ||
    normalizedLabel.includes('other')
  );
}

function distanceBetweenPointsMeters(start: Point, end: Point): number {
  const toRad = (value: number) => (value * Math.PI) / 180;
  const earthRadius = 6371000;
  const deltaLat = toRad(end.latitude - start.latitude);
  const deltaLon = toRad(end.longitude - start.longitude);
  const lat1 = toRad(start.latitude);
  const lat2 = toRad(end.latitude);
  const a =
    Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2) +
    Math.cos(lat1) * Math.cos(lat2) * Math.sin(deltaLon / 2) * Math.sin(deltaLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return earthRadius * c;
}

function parseGeojsonPoint(value: unknown): Point | null {
  if (!Array.isArray(value) || value.length < 2) {
    return null;
  }
  const longitude = Number(value[0]);
  const latitude = Number(value[1]);
  if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
    return null;
  }
  return { latitude, longitude };
}

function parseGeojsonPolygon(value: unknown): Point[] {
  if (!Array.isArray(value) || value.length === 0 || !Array.isArray(value[0])) {
    return [];
  }
  return (value[0] as unknown[])
    .map((coord) => parseGeojsonPoint(coord))
    .filter((coord): coord is Point => coord !== null);
}

function parseGeometryFromGeojson(
  geojson: unknown,
): { geometryType: 'point' | 'polygon' | null; point: Point | null; polygon: Point[] } {
  if (!geojson || typeof geojson !== 'object') {
    return { geometryType: null, point: null, polygon: [] };
  }
  const feature = (geojson as any)?.features?.[0];
  const geometryType = String(feature?.geometry?.type ?? '').trim();
  if (geometryType === 'Point') {
    const point = parseGeojsonPoint(feature?.geometry?.coordinates);
    return { geometryType: point ? 'point' : null, point, polygon: [] };
  }
  if (geometryType === 'Polygon') {
    const polygon = parseGeojsonPolygon(feature?.geometry?.coordinates);
    return { geometryType: polygon.length > 0 ? 'polygon' : null, point: null, polygon };
  }
  return { geometryType: null, point: null, polygon: [] };
}

function normalizeServerBaseUrl(value: string): string {
  const normalized = String(value || '').trim();
  if (!normalized) {
    return '';
  }
  return normalized.replace(/\/+$/, '');
}

function ensureDatabase(): void {
  db.execSync(`
    CREATE TABLE IF NOT EXISTS mobile_session (
      id INTEGER PRIMARY KEY CHECK (id = 1),
      user_json TEXT NOT NULL,
      updated_at TEXT NOT NULL
    );
  `);

  db.execSync(`
    CREATE TABLE IF NOT EXISTS mobile_forms (
      id TEXT PRIMARY KEY,
      type TEXT NOT NULL,
      campaign_id INTEGER,
      site_id INTEGER NOT NULL,
      sector TEXT,
      payload TEXT NOT NULL,
      status TEXT NOT NULL DEFAULT 'pending',
      sync_error TEXT,
      created_at TEXT NOT NULL,
      synced_at TEXT
    );
  `);

  db.execSync(`
    CREATE TABLE IF NOT EXISTS mobile_campaigns (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      site_id INTEGER NOT NULL,
      site_label TEXT NOT NULL,
      period_mm_yyyy TEXT NOT NULL,
      is_archived INTEGER NOT NULL DEFAULT 0,
      created_at TEXT NOT NULL,
      updated_at TEXT NOT NULL
    );
  `);

  db.execSync(`
    CREATE TABLE IF NOT EXISTS mobile_reference_cache (
      cache_key TEXT PRIMARY KEY,
      payload TEXT NOT NULL,
      updated_at TEXT NOT NULL
    );
  `);

  db.execSync(`
    CREATE TABLE IF NOT EXISTS mobile_settings (
      key TEXT PRIMARY KEY,
      value TEXT NOT NULL,
      updated_at TEXT NOT NULL
    );
  `);

  try {
    db.execSync('ALTER TABLE mobile_forms ADD COLUMN campaign_id INTEGER;');
  } catch (_error) {
    // Colonne déjà existante
  }
  try {
    db.execSync('ALTER TABLE mobile_campaigns ADD COLUMN is_archived INTEGER NOT NULL DEFAULT 0;');
  } catch (_error) {
    // Colonne déjà existante
  }
  const mobileFormColumns = (db.getAllSync('PRAGMA table_info(mobile_forms)') || []) as Array<{ name?: string }>;
  if (!mobileFormColumns.some((column) => column.name === 'sync_error')) {
    db.execSync('ALTER TABLE mobile_forms ADD COLUMN sync_error TEXT;');
  }
}

async function readStoredSession(): Promise<User | null> {
  try {
    const row = db.getFirstSync('SELECT user_json FROM mobile_session WHERE id = 1') as { user_json?: string } | null;
    if (!row || !row.user_json) {
      return null;
    }

    return JSON.parse(String(row.user_json)) as User;
  } catch (error) {
    console.warn('Session read failed', error);
    return null;
  }
}

function writeStoredSession(user: User): void {
  db.runSync(
    'INSERT OR REPLACE INTO mobile_session (id, user_json, updated_at) VALUES (?, ?, ?)',
    [1, JSON.stringify(user), new Date().toISOString()],
  );
}

function clearStoredSession(): void {
  db.runSync('DELETE FROM mobile_session WHERE id = 1');
}

function readCachedReferences(): CachedReferencePayload | null {
  try {
    const row = db.getFirstSync(
      'SELECT payload FROM mobile_reference_cache WHERE cache_key = ? LIMIT 1',
      ['references'],
    ) as { payload?: string } | null;
    if (!row?.payload) {
      return null;
    }
    const parsed = JSON.parse(String(row.payload)) as CachedReferencePayload;
    if (!parsed || typeof parsed !== 'object') {
      return null;
    }
    return {
      provinces: Array.isArray(parsed.provinces) ? parsed.provinces : [],
      territoires: Array.isArray(parsed.territoires) ? parsed.territoires : [],
      communes: Array.isArray(parsed.communes) ? parsed.communes : [],
      sites: Array.isArray(parsed.sites) ? parsed.sites : [],
      movement_reasons: Array.isArray(parsed.movement_reasons) ? parsed.movement_reasons : [],
    };
  } catch (error) {
    console.warn('Reference cache read failed', error);
    return null;
  }
}

function writeCachedReferences(payload: CachedReferencePayload): void {
  db.runSync(
    'INSERT OR REPLACE INTO mobile_reference_cache (cache_key, payload, updated_at) VALUES (?, ?, ?)',
    ['references', JSON.stringify(payload), new Date().toISOString()],
  );
}

function readMobileSetting(key: string): string | null {
  const row = db.getFirstSync(
    'SELECT value FROM mobile_settings WHERE key = ? LIMIT 1',
    [key],
  ) as { value?: string } | null;
  return row?.value ? String(row.value) : null;
}

function writeMobileSetting(key: string, value: string): void {
  db.runSync(
    'INSERT OR REPLACE INTO mobile_settings (key, value, updated_at) VALUES (?, ?, ?)',
    [key, value, new Date().toISOString()],
  );
}

function readStoredForms(): FormRecord[] {
  const rows = (db.getAllSync(
    'SELECT id, type, campaign_id, site_id, sector, payload, status, sync_error, created_at FROM mobile_forms ORDER BY created_at DESC',
  ) || []) as Array<{ id: string; type: string; campaign_id?: number | null; site_id: number; sector?: string; payload: string; status: string; sync_error?: string | null; created_at: string }>;

  return rows.map((row) => {
    const payload = JSON.parse(String(row.payload)) as Record<string, any>;
    return {
      id: row.id,
      type: row.type === 'geography'
        ? 'geography'
        : row.type === 'ossat'
        ? 'ossat'
        : row.type === 'questionnaire'
        ? 'questionnaire'
        : row.type === 'movement'
        ? 'movement'
        : 'sector',
      campaign_id: row.campaign_id ? Number(row.campaign_id) : null,
      site_id: Number(row.site_id),
      sector: (row.sector as SectorKey | undefined) ?? undefined,
      payload,
      status: row.status === 'synced'
        ? 'synced'
        : row.status === 'draft'
        ? 'draft'
        : row.status === 'correction'
        ? 'correction'
        : 'pending',
      sync_error: row.sync_error ? String(row.sync_error) : null,
      sync_error_details: parseSyncErrorDetails(payload._sync_error_details, String(row.sync_error ?? '')),
      created_at: row.created_at,
    };
  });
}

function saveFormToDb(record: FormRecord): void {
  db.runSync(
    `INSERT OR REPLACE INTO mobile_forms (id, type, campaign_id, site_id, sector, payload, status, sync_error, created_at, synced_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      record.id,
      record.type,
      record.campaign_id ?? null,
      record.site_id,
      record.sector ?? null,
      JSON.stringify({
        ...record.payload,
        _sync_error_details: record.sync_error_details ?? [],
      }),
      record.status,
      record.sync_error ?? null,
      record.created_at,
      record.status === 'synced' ? new Date().toISOString() : null,
    ],
  );
}

function readReconciledStoredForms(): FormRecord[] {
  return readStoredForms().map((record) => {
    if (record.type !== 'movement' || record.status !== 'pending') {
      return record;
    }

    const conformity = checkSyncConformity(record);
    if (conformity.valid) {
      return record;
    }

    const correctionRecord: FormRecord = {
      ...record,
      status: 'correction',
      sync_error: conformity.errors.join(' '),
      sync_error_details: [],
    };
    saveFormToDb(correctionRecord);

    return correctionRecord;
  });
}

function deleteFormFromDb(id: string): void {
  db.runSync('DELETE FROM mobile_forms WHERE id = ?', [id]);
}

function createCampaign(userId: number, siteId: number, siteLabel: string, period: string): number {
  const now = new Date().toISOString();
  db.runSync(
    `INSERT INTO mobile_campaigns (user_id, site_id, site_label, period_mm_yyyy, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?)`,
    [userId, siteId, siteLabel, period, now, now],
  );
  const row = db.getFirstSync('SELECT last_insert_rowid() AS id') as { id?: number } | null;
  return Number(row?.id ?? 0);
}

function readCampaignsByUser(userId: number): CollectionCampaign[] {
  const rows = (db.getAllSync(
    `SELECT id, user_id, site_id, site_label, period_mm_yyyy, is_archived, created_at, updated_at
     FROM mobile_campaigns
     WHERE user_id = ?
     ORDER BY created_at DESC`,
    [userId],
  ) || []) as Array<{
    id: number;
    user_id: number;
    site_id: number;
    site_label: string;
    period_mm_yyyy: string;
    is_archived?: number | null;
    created_at: string;
    updated_at: string;
  }>;

  return rows.map((row) => ({
    id: Number(row.id),
    user_id: Number(row.user_id),
    site_id: Number(row.site_id),
    site_label: String(row.site_label || ''),
    period_mm_yyyy: String(row.period_mm_yyyy || ''),
    is_archived: Number(row.is_archived ?? 0) === 1,
    created_at: String(row.created_at || ''),
    updated_at: String(row.updated_at || ''),
  }));
}

function updateCampaign(
  campaignId: number,
  siteId: number,
  siteLabel: string,
  period: string,
): void {
  db.runSync(
    `UPDATE mobile_campaigns
     SET site_id = ?, site_label = ?, period_mm_yyyy = ?, updated_at = ?
     WHERE id = ?`,
    [siteId, siteLabel, period, new Date().toISOString(), campaignId],
  );
}

function updatePendingFormsForCampaign(
  campaignId: number,
  siteId: number,
  period: string,
): void {
  const rows = (db.getAllSync(
    'SELECT id, type, payload FROM mobile_forms WHERE campaign_id = ? AND status = ?',
    [campaignId, 'pending'],
  ) || []) as Array<{ id: string; type: string; payload: string }>;

  const dateCollecte = periodToCollecteDate(period);

  for (const row of rows) {
    let payload: Record<string, any> = {};
    try {
      payload = JSON.parse(String(row.payload || '{}'));
    } catch (_error) {
      payload = {};
    }

    payload.site_id = siteId;
    payload.campaign_id = campaignId;
    payload.periode_collecte = period;
    payload.date_collecte = dateCollecte;

    db.runSync(
      'UPDATE mobile_forms SET site_id = ?, payload = ? WHERE id = ?',
      [siteId, JSON.stringify(payload), row.id],
    );
  }
}

function archiveCampaign(campaignId: number): void {
  db.runSync(
    'UPDATE mobile_campaigns SET is_archived = 1, updated_at = ? WHERE id = ?',
    [new Date().toISOString(), campaignId],
  );
}

function pickPhoto(): Promise<string | null> {
  return new Promise(async (resolve) => {
    try {
      const permission = await ImagePicker.requestCameraPermissionsAsync();
      if (!permission.granted) {
        Alert.alert('Permission photo', 'Autorisez l’accès à l’appareil photo pour prendre des photos.');
        resolve(null);
        return;
      }

      const result = await ImagePicker.launchCameraAsync({
        mediaTypes: ImagePicker.MediaTypeOptions.Images,
        allowsEditing: true,
        quality: 0.8,
      });

      if (!result.canceled && result.assets?.[0]?.uri) {
        resolve(result.assets[0].uri);
      } else {
        resolve(null);
      }
    } catch (error) {
      console.warn('Photo capture failed', error);
      resolve(null);
    }
  });
}

export default function App() {
  const [user, setUser] = useState<User | null>(null);
  const [email, setEmail] = useState('heaney.titus@example.org');
  const [password, setPassword] = useState('password');
  const [serverBaseUrl, setServerBaseUrl] = useState(DEFAULT_API_BASE);
  const [tab, setTab] = useState<TabKey>('dashboard');
  const [siteId, setSiteId] = useState('');
  const [siteSelectionMode, setSiteSelectionMode] = useState<SiteSelectionMode>('existing');
  const [newSiteData, setNewSiteData] = useState<NewSiteFormData>(INITIAL_NEW_SITE_FORM);
  const [isCompactMode, setIsCompactMode] = useState(false);
  const [activeTab, setActiveTab] = useState<'dashboard' | 'collecte' | 'geography' | 'sync' | 'profile'>('dashboard');
  const [readySyncOrigin, setReadySyncOrigin] = useState<ReadySyncOrigin>('dashboard');
  const [dateCollecte, setDateCollecte] = useState(formatDate(new Date()));
  const [selectedSector, setSelectedSector] = useState<SectorKey>('wash');
  const [ossatData, setOssatData] = useState<Record<string, string>>({
    date_collecte: formatDate(new Date()),
    agent_collecteur: user?.name ?? 'Agent OSSAT',
    statut: 'soumis',
    source_information: 'application_mobile',
  });
  const [sectorData, setSectorData] = useState<Record<string, string>>({
    date_collecte: formatDate(new Date()),
  });
  const [queue, setQueue] = useState<FormRecord[]>([]);
  const [campaigns, setCampaigns] = useState<CollectionCampaign[]>([]);
  const [activeCampaignId, setActiveCampaignId] = useState<number | null>(null);
  const [campaignTypeFilter, setCampaignTypeFilter] = useState<CampaignTypeFilter>('all');
  const [campaignSyncFilter, setCampaignSyncFilter] = useState<CampaignSyncFilter>('all');
  const [campaignPanelMode, setCampaignPanelMode] = useState<'standard' | 'menu' | 'create'>('standard');
  const [collecteListMode, setCollecteListMode] = useState<'none' | 'draft' | 'pending' | 'synced'>('none');
  const [campaignPeriodInput, setCampaignPeriodInput] = useState(() => {
    const now = new Date();
    return `${String(now.getMonth() + 1).padStart(2, '0')}-${now.getFullYear()}`;
  });
  const safeQueue = Array.isArray(queue) ? queue : [];
  const todayLabel = new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
  const todayCollectionCount = safeQueue.filter((item) => item.created_at?.startsWith(new Date().toISOString().slice(0, 10))).length;
  const activeSitesCount = Math.max(1, Number(siteId) || 1);
  const bottomNavItems = [
    { key: 'dashboard', label: 'Accueil', icon: '🏠' },
    { key: 'collecte', label: 'Collecte', icon: '📝' },
    { key: 'geography', label: 'Carte', icon: '📍' },
    { key: 'sync', label: 'Sync', icon: '⇄' },
    { key: 'profile', label: 'Compte', icon: '👤' },
  ] as const;
  const [geometryType, setGeometryType] = useState<'point' | 'polygon'>('polygon');
  const [gpsPointCategory, setGpsPointCategory] = useState<GpsPointCategory | ''>('');
  const [gpsPointOtherLabel, setGpsPointOtherLabel] = useState('');
  const [gpsPolygonCategory, setGpsPolygonCategory] = useState<GpsPolygonCategory | ''>('');
  const [gpsPolygonBlockName, setGpsPolygonBlockName] = useState('');
  const [errorMargin, setErrorMargin] = useState('10');
  const [polygonPoints, setPolygonPoints] = useState<Point[]>([]);
  const [point, setPoint] = useState<Point | null>(null);
  const [mapRegion, setMapRegion] = useState<Region>(INITIAL_REGION);
  const [isPolygonTracking, setIsPolygonTracking] = useState(false);
  const [geographyPanelMode, setGeographyPanelMode] = useState<'list' | 'form'>('list');
  const [geographyListMode, setGeographyListMode] = useState<'all' | 'draft' | 'pending' | 'synced'>('all');
  const [editingGeographyFormId, setEditingGeographyFormId] = useState<string | null>(null);
  const [editingGeographyCreatedAt, setEditingGeographyCreatedAt] = useState<string | null>(null);
  const [movementPanelMode, setMovementPanelMode] = useState<'list' | 'form'>('list');
  const [movementListMode, setMovementListMode] = useState<'all' | 'draft' | 'pending' | 'synced' | 'correction'>('all');
  const [movementData, setMovementData] = useState<Record<string, string>>({ ...INITIAL_MOVEMENT_DATA });
  const [editingMovementFormId, setEditingMovementFormId] = useState<string | null>(null);
  const [editingMovementCreatedAt, setEditingMovementCreatedAt] = useState<string | null>(null);
  const [editingMovementSyncError, setEditingMovementSyncError] = useState<string | null>(null);
  const [photos, setPhotos] = useState<string[]>([]);
  const [questionnaire, setQuestionnaire] = useState<QuestionnaireDefinition | null>(null);
  const [questionnaireAnswers, setQuestionnaireAnswers] = useState<Record<string, string>>({});
  const [invalidRequiredQuestionKeys, setInvalidRequiredQuestionKeys] = useState<Record<string, boolean>>({});
  const [collecteMetaErrors, setCollecteMetaErrors] = useState<{ site: boolean; date: boolean }>({ site: false, date: false });
  const [editingFormId, setEditingFormId] = useState<string | null>(null);
  const [editingFormCreatedAt, setEditingFormCreatedAt] = useState<string | null>(null);
  const [openedSavedFormStatus, setOpenedSavedFormStatus] = useState<'draft' | 'pending' | null>(null);
  const [selectedPendingFormIds, setSelectedPendingFormIds] = useState<Record<string, boolean>>({});
  const [selectedReadyFormIds, setSelectedReadyFormIds] = useState<Record<string, boolean>>({});
  const readyForms = safeQueue
    .filter((item) => item.status === 'pending')
    .map((item) => ({ record: item, conformity: checkSyncConformity(item) }));
  const conformingReadyForms = readyForms.filter((item) => item.conformity.valid);
  const selectedReadyCount = conformingReadyForms.filter(
    (item) => selectedReadyFormIds[item.record.id],
  ).length;
  const [activeQuestionnaireSection, setActiveQuestionnaireSection] = useState('default');
  const [activeQuestionnaireSubgroup, setActiveQuestionnaireSubgroup] = useState('__all__');
  const [isQuestionnaireLoading, setIsQuestionnaireLoading] = useState(false);
  const [isSyncing, setIsSyncing] = useState(false);
  const [referenceProvinces, setReferenceProvinces] = useState<ProvinceRef[]>([]);
  const [referenceTerritoires, setReferenceTerritoires] = useState<TerritoireRef[]>([]);
  const [referenceCommunes, setReferenceCommunes] = useState<CommuneRef[]>([]);
  const [referenceSites, setReferenceSites] = useState<SiteRef[]>([]);
  const [movementReasons, setMovementReasons] = useState<MovementReasonRef[]>([]);
  const [existingSitePoint, setExistingSitePoint] = useState<Point | null>(null);
  const [existingSitePolygonPoints, setExistingSitePolygonPoints] = useState<Point[]>([]);
  const [selectedProvinceId, setSelectedProvinceId] = useState('');
  const [selectedTerritoireId, setSelectedTerritoireId] = useState('');
  const [selectedCommuneId, setSelectedCommuneId] = useState('');
  const [activePickerKey, setActivePickerKey] = useState<string | null>(null);
  const [pickerSearchMap, setPickerSearchMap] = useState<Record<string, string>>({});
  const polygonTrackingSubscriptionRef = useRef<Location.LocationSubscription | null>(null);
  const { height: screenHeight } = useWindowDimensions();
  const picklistMaxHeight = Math.min(240, Math.max(140, Math.floor(screenHeight * 0.3)));
  const apiBase = useMemo(
    () => normalizeServerBaseUrl(serverBaseUrl) || DEFAULT_API_BASE,
    [serverBaseUrl],
  );

  useEffect(() => {
    ensureDatabase();
    const bootstrap = async () => {
      const storedUser = await readStoredSession();
      if (storedUser) {
        setUser(storedUser);
      }
      const storedApiBase = normalizeServerBaseUrl(readMobileSetting('api_base') || '');
      if (storedApiBase) {
        setServerBaseUrl(storedApiBase);
      }
      const cachedReferences = readCachedReferences();
      if (cachedReferences) {
        setReferenceProvinces(cachedReferences.provinces);
        setReferenceTerritoires(cachedReferences.territoires);
        setReferenceCommunes(cachedReferences.communes);
        setReferenceSites(cachedReferences.sites);
        setMovementReasons(cachedReferences.movement_reasons);
      }
      setQueue(readReconciledStoredForms());
      void getCurrentLocation();
    };

    bootstrap();
  }, []);

  useEffect(() => {
    setIsCompactMode(Platform.OS === 'ios');
  }, []);

  useEffect(() => {
    setOssatData((prev) => ({
      ...prev,
      agent_collecteur: user?.name ?? prev.agent_collecteur ?? 'Agent OSSAT',
    }));
  }, [user]);

  const loadQuestionnaire = useCallback(async (silent = false): Promise<boolean> => {
    if (!user) {
      return false;
    }

    setIsQuestionnaireLoading(true);
    try {
      const response = await fetch(`${apiBase}/api/mobile/questionnaires/active?code=service-cartography&t=${Date.now()}`, {
        headers: {
          Accept: 'application/json',
          'Cache-Control': 'no-cache',
        },
      });
      const payload = await response.json();

      if (!response.ok || !payload?.success || !payload?.questionnaire) {
        throw new Error(payload?.message || 'Questionnaire mobile indisponible.');
      }

      const nextQuestionnaire = payload.questionnaire as QuestionnaireDefinition;
      nextQuestionnaire.choices = Array.isArray(nextQuestionnaire.choices)
        ? nextQuestionnaire.choices.map((choice) => ({
            ...choice,
            list_name: String(choice.list_name ?? choice.listName ?? '').trim(),
            label: String(choice.label ?? choice.label_fr ?? choice.label_en ?? choice.name ?? '').trim(),
          }))
        : [];
      const references = payload?.references ?? {};
      const nextProvinces = Array.isArray(references.provinces) ? references.provinces : [];
      const nextTerritoires = Array.isArray(references.territoires) ? references.territoires : [];
      const nextCommunes = Array.isArray(references.communes) ? references.communes : [];
      const nextSites = Array.isArray(references.sites) ? references.sites : [];
      const nextMovementReasons = Array.isArray(references.movement_reasons) ? references.movement_reasons : [];
      setReferenceProvinces(nextProvinces);
      setReferenceTerritoires(nextTerritoires);
      setReferenceCommunes(nextCommunes);
      setReferenceSites(nextSites);
      setMovementReasons(nextMovementReasons);
      writeCachedReferences({
        provinces: nextProvinces,
        territoires: nextTerritoires,
        communes: nextCommunes,
        sites: nextSites,
        movement_reasons: nextMovementReasons,
      });
      setQuestionnaire((previous) => {
        if (!previous || previous.id !== nextQuestionnaire.id || previous.version !== nextQuestionnaire.version) {
          setQuestionnaireAnswers({});
          setActiveQuestionnaireSection('default');
          setActiveQuestionnaireSubgroup('__all__');
        }
        return nextQuestionnaire;
      });
      return true;
    } catch (error) {
      if (!silent) {
        Alert.alert('Questionnaire', error instanceof Error ? error.message : 'Impossible de charger le questionnaire.');
      }
      return false;
    } finally {
      setIsQuestionnaireLoading(false);
    }
  }, [user, apiBase]);

  const syncSiteReferences = async () => {
    if (!user) {
      Alert.alert('Connexion requise', 'Connectez-vous avant la synchronisation.');
      return;
    }

    const pendingGeographyIds = readStoredForms()
      .filter((item) => item.status === 'pending' && item.type === 'geography')
      .map((item) => item.id);

    if (pendingGeographyIds.length > 0) {
      const syncedGeography = await syncQueue(undefined, pendingGeographyIds);
      if (!syncedGeography) {
        return;
      }

      const refreshedAfterSync = await loadQuestionnaire(true);
      if (!refreshedAfterSync) {
        Alert.alert('Synchronisation sites', 'Cartographie envoyée, mais impossible d’actualiser la liste des sites. Réessayez.');
      }
      return;
    }

    const synced = await loadQuestionnaire(false);
    if (!synced) {
      return;
    }
    Alert.alert('Synchronisation sites', 'La liste des sites et la cartographie existante ont été mises à jour sur ce mobile.');
  };

  const stopPolygonTracking = useCallback(() => {
    if (polygonTrackingSubscriptionRef.current) {
      polygonTrackingSubscriptionRef.current.remove();
      polygonTrackingSubscriptionRef.current = null;
    }
    setIsPolygonTracking(false);
  }, []);

  useEffect(() => {
    if (!user) {
      return;
    }

    void loadQuestionnaire(false);
  }, [user, loadQuestionnaire]);

  useEffect(() => {
    if (!user || activeTab !== 'collecte' || tab !== 'sector') {
      return;
    }

    void loadQuestionnaire(true);
  }, [user, activeTab, tab, loadQuestionnaire]);

  useEffect(() => () => {
    stopPolygonTracking();
  }, [stopPolygonTracking]);

  const sectorFields = useMemo(() => sectorFieldMap[selectedSector], [selectedSector]);
  const questionnaireSections = useMemo(() => {
    const survey = questionnaire?.survey ?? [];
    const sectionsByKey: Record<string, QuestionnaireSection> = {
      default: { key: 'default', label: 'Général', children: [] },
    };
    const stack: Array<{ key: string; label: string }> = [{ key: 'default', label: 'Général' }];

    for (const row of survey) {
      const type = String(row.type || '').toLowerCase();
      if (type.startsWith('begin_group') || type.startsWith('begin_repeat')) {
        const key = String(row.name || '').trim() || 'default';
        const label = String(row.label || row.label_fr || key || 'Section').trim();
        stack.push({ key, label });
        const depth = stack.length - 1;

        if (depth === 1 && key !== 'default' && !sectionsByKey[key]) {
          sectionsByKey[key] = { key, label, children: [] };
        }

        if (depth >= 2) {
          const parent = stack[1]?.key || 'default';
          if (!sectionsByKey[parent]) {
            sectionsByKey[parent] = {
              key: parent,
              label: String(stack[1]?.label || parent),
              children: [],
            };
          }

          const pathSegments = stack.slice(2);
          const childKey = pathSegments.map((item) => item.key).join('::');
          const childLabel = pathSegments.map((item) => item.label).join(' > ');
          if (childKey && !sectionsByKey[parent].children.some((child) => child.key === childKey)) {
            sectionsByKey[parent].children.push({ key: childKey, label: childLabel || label });
          }
        }
        continue;
      }

      if (!(type.startsWith('end_group') || type.startsWith('end_repeat'))) {
        continue;
      }

      if (stack.length > 1) {
        stack.pop();
      }
    }

    return Object.values(sectionsByKey);
  }, [questionnaire]);
  const collecteMenuSections = useMemo(
    () =>
      questionnaire
        ? questionnaireSections
        : (Object.keys(sectorFieldMap) as SectorKey[]).map((sector) => ({
            key: sector,
            label:
              sector === 'wash' ? 'WASH' :
              sector === 'sante' ? 'Santé' :
              sector === 'protection' ? 'Prot.' :
              sector === 'education' ? 'Édu.' :
              sector === 'environnement' ? 'Env.' :
              'Abri',
          })),
    [questionnaire, questionnaireSections],
  );

  const questionnaireQuestions = useMemo(() => {
    const survey = questionnaire?.survey ?? [];
    const result: Record<string, QuestionnaireRenderedQuestion[]> = { default: [] };
    const stack: Array<{ key: string; label: string }> = [];

    for (const row of survey) {
      const type = String(row.type || '').toLowerCase();

      if (type.startsWith('begin_group') || type.startsWith('begin_repeat')) {
        const groupName = String(row.name || '').trim() || 'default';
        const groupLabel = String(row.label || row.label_fr || groupName || 'Section').trim();
        stack.push({ key: groupName, label: groupLabel });
        if (stack.length === 1 && !result[groupName]) {
          result[groupName] = [];
        }
        continue;
      }

      if (type.startsWith('end_group') || type.startsWith('end_repeat')) {
        stack.pop();
        continue;
      }

      if (
        type === 'start' ||
        type === 'end' ||
        type === 'today' ||
        type === 'deviceid' ||
        type === 'phonenumber' ||
        type === 'calculate'
      ) {
        continue;
      }

      const currentSection = stack.length > 0 ? stack[0].key : 'default';
      if (!result[currentSection]) {
        result[currentSection] = [];
      }

      let subgroupKey: string | null = null;
      let subgroupLabel: string | null = null;
      if (stack.length > 1) {
        subgroupKey = stack
          .slice(1)
          .map((item) => item.key)
          .join('::');
        subgroupLabel = stack
          .slice(1)
          .map((item) => item.label)
          .join(' > ');
      }

      result[currentSection].push({
        ...row,
        subgroup_key: subgroupKey && subgroupKey !== '' ? subgroupKey : null,
        subgroup_label: subgroupLabel && subgroupLabel !== '' ? subgroupLabel : null,
      });
    }

    return result;
  }, [questionnaire]);

  const activeQuestionnaireSectionMeta = useMemo(
    () => questionnaireSections.find((section) => section.key === activeQuestionnaireSection) ?? questionnaireSections[0] ?? null,
    [questionnaireSections, activeQuestionnaireSection],
  );
  const hasParentQuestions = useMemo(
    () => (questionnaireQuestions[activeQuestionnaireSection] ?? []).some((question) => !question.subgroup_key),
    [questionnaireQuestions, activeQuestionnaireSection],
  );
  const activeQuestionnaireQuestions = useMemo(
    () => {
      const questions = questionnaireQuestions[activeQuestionnaireSection] ?? questionnaireQuestions.default ?? [];
      if (activeQuestionnaireSubgroup === '__all__') {
        return questions;
      }
      if (activeQuestionnaireSubgroup === '__parent__') {
        return questions.filter((question) => !question.subgroup_key);
      }
      return questions.filter((question) => question.subgroup_key === activeQuestionnaireSubgroup);
    },
    [questionnaireQuestions, activeQuestionnaireSection, activeQuestionnaireSubgroup],
  );
  const isPolygonMode = siteSelectionMode === 'new' || geometryType === 'polygon';

  useEffect(() => {
    if (geographyPanelMode !== 'form' || !isPolygonMode) {
      stopPolygonTracking();
    }
  }, [geographyPanelMode, isPolygonMode, stopPolygonTracking]);
  const questionnaireQuestionTotal = useMemo(
    () => (questionnaireQuestions[activeQuestionnaireSection] ?? questionnaireQuestions.default ?? []).length,
    [questionnaireQuestions, activeQuestionnaireSection],
  );
  const campaignOverviews = useMemo<CampaignOverview[]>(() => {
    const forms = safeQueue;
    return campaigns
      .filter((campaign) => !campaign.is_archived)
      .map((campaign) => {
      const campaignForms = forms.filter((item) => Number(item.campaign_id ?? 0) === campaign.id);
      const draftCount = campaignForms.filter((item) => item.status === 'draft').length;
      const pendingCount = campaignForms.filter((item) => item.status === 'pending').length;
      const syncedCount = campaignForms.filter((item) => item.status === 'synced').length;
      const totalForms = campaignForms.length;
      const status: CampaignStatus = pendingCount > 0
        ? 'en_attente'
        : totalForms === 0 || draftCount > 0
        ? 'brouillon'
        : 'synchronise';

      return {
        ...campaign,
        pending_count: pendingCount,
        synced_count: syncedCount,
        total_forms: totalForms,
        status,
      };
    });
  }, [campaigns, safeQueue]);
  const archivedCampaignOverviews = useMemo<CampaignOverview[]>(() => {
    const forms = safeQueue;
    return campaigns
      .filter((campaign) => campaign.is_archived)
      .map((campaign) => {
        const campaignForms = forms.filter((item) => Number(item.campaign_id ?? 0) === campaign.id);
        const draftCount = campaignForms.filter((item) => item.status === 'draft').length;
        const pendingCount = campaignForms.filter((item) => item.status === 'pending').length;
        const syncedCount = campaignForms.filter((item) => item.status === 'synced').length;
        const totalForms = campaignForms.length;
        const status: CampaignStatus = pendingCount > 0
          ? 'en_attente'
          : totalForms === 0 || draftCount > 0
          ? 'brouillon'
          : 'synchronise';

        return {
          ...campaign,
          pending_count: pendingCount,
          synced_count: syncedCount,
          total_forms: totalForms,
          status,
        };
      });
  }, [campaigns, safeQueue]);
  const activeCampaign = useMemo(
    () => campaignOverviews.find((campaign) => campaign.id === activeCampaignId) ?? null,
    [campaignOverviews, activeCampaignId],
  );
  const campaignSummary = useMemo(
    () => ({
      brouillon: campaignOverviews.filter((campaign) => campaign.status === 'brouillon').length,
      en_attente: campaignOverviews.filter((campaign) => campaign.status === 'en_attente').length,
      synchronise: campaignOverviews.filter((campaign) => campaign.status === 'synchronise').length,
    }),
    [campaignOverviews],
  );
  const activeCampaignTimeline = useMemo(
    () =>
      safeQueue
        .filter((item) => Number(item.campaign_id ?? 0) === Number(activeCampaignId ?? 0))
        .sort((a, b) => String(b.created_at).localeCompare(String(a.created_at))),
    [safeQueue, activeCampaignId],
  );
  const filteredCampaignTimeline = useMemo(
    () =>
      activeCampaignTimeline.filter((item) => {
        const matchesType = campaignTypeFilter === 'all' || item.type === campaignTypeFilter;
        const matchesStatus = campaignSyncFilter === 'all' || item.status === campaignSyncFilter;
        return matchesType && matchesStatus;
      }),
    [activeCampaignTimeline, campaignTypeFilter, campaignSyncFilter],
  );
  const activeServiceCollecteForms = useMemo(
    () => activeCampaignTimeline.filter((item) => item.type === 'questionnaire' || item.type === 'sector'),
    [activeCampaignTimeline],
  );
  const serviceCollecteForms = useMemo(
    () =>
      safeQueue
        .filter((item) => item.type === 'questionnaire' || item.type === 'sector')
        .sort((a, b) => String(b.created_at).localeCompare(String(a.created_at))),
    [safeQueue],
  );
  const filteredServiceCollecteForms = useMemo(() => {
    if (collecteListMode === 'draft') {
      return serviceCollecteForms.filter((item) => item.status === 'draft');
    }
    if (collecteListMode === 'pending') {
      return serviceCollecteForms.filter((item) => item.status === 'pending');
    }
    if (collecteListMode === 'synced') {
      return serviceCollecteForms.filter((item) => item.status === 'synced');
    }
    return [];
  }, [serviceCollecteForms, collecteListMode]);
  const geographyForms = useMemo(
    () =>
      safeQueue
        .filter((item) => item.type === 'geography')
        .sort((a, b) => String(b.created_at).localeCompare(String(a.created_at))),
    [safeQueue],
  );
  const geographySummary = useMemo(
    () => ({
      brouillon: geographyForms.filter((item) => item.status === 'draft').length,
      en_attente: geographyForms.filter((item) => item.status === 'pending').length,
      synchronise: geographyForms.filter((item) => item.status === 'synced').length,
    }),
    [geographyForms],
  );
  const filteredGeographyForms = useMemo(() => {
    if (geographyListMode === 'draft') {
      return geographyForms.filter((item) => item.status === 'draft');
    }
    if (geographyListMode === 'pending') {
      return geographyForms.filter((item) => item.status === 'pending');
    }
    if (geographyListMode === 'synced') {
      return geographyForms.filter((item) => item.status === 'synced');
    }
    return geographyForms;
  }, [geographyForms, geographyListMode]);
  const movementForms = useMemo(
    () =>
      safeQueue
        .filter((item) => item.type === 'movement')
        .sort((a, b) => String(b.created_at).localeCompare(String(a.created_at))),
    [safeQueue],
  );
  const movementSummary = useMemo(
    () => ({
      brouillon: movementForms.filter((item) => item.status === 'draft').length,
      en_attente: movementForms.filter((item) => item.status === 'pending').length,
      synchronise: movementForms.filter((item) => item.status === 'synced').length,
      a_corriger: movementForms.filter((item) => item.status === 'correction').length,
    }),
    [movementForms],
  );
  const filteredMovementForms = useMemo(() => {
    if (movementListMode === 'draft') {
      return movementForms.filter((item) => item.status === 'draft');
    }
    if (movementListMode === 'pending') {
      return movementForms.filter((item) => item.status === 'pending');
    }
    if (movementListMode === 'synced') {
      return movementForms.filter((item) => item.status === 'synced');
    }
    if (movementListMode === 'correction') {
      return movementForms.filter((item) => item.status === 'correction');
    }
    return movementForms;
  }, [movementForms, movementListMode]);
  const movementIndividualsTotal = useMemo(
    () => MOVEMENT_DEMOGRAPHIC_FIELDS.reduce(
      (total, field) => total + (Number.parseInt(movementData[field.key] || '0', 10) || 0),
      0,
    ),
    [movementData],
  );
  const filteredMovementReasons = useMemo(() => {
    const type = movementData.type_mouvement as MovementType;
    if (type !== 'arrivee' && type !== 'depart') {
      return [];
    }
    const expectedTerms = type === 'arrivee'
      ? ['entree', 'entrée', 'arrivee', 'arrivée']
      : ['sortie', 'depart', 'départ'];
    return movementReasons.filter((reason) => {
      const category = `${reason.category_name ?? ''} ${reason.category_code ?? ''}`.toLowerCase();
      return expectedTerms.some((term) => category.includes(term));
    });
  }, [movementData.type_mouvement, movementReasons]);
  const selectedPendingCount = useMemo(
    () => Object.values(selectedPendingFormIds).filter(Boolean).length,
    [selectedPendingFormIds],
  );
  const draftFormCount = useMemo(
    () => serviceCollecteForms.filter((item) => item.status === 'draft').length,
    [serviceCollecteForms],
  );
  const pendingFormCount = useMemo(
    () => serviceCollecteForms.filter((item) => item.status === 'pending').length,
    [serviceCollecteForms],
  );
  const syncedFormCount = useMemo(
    () => serviceCollecteForms.filter((item) => item.status === 'synced').length,
    [serviceCollecteForms],
  );

  useEffect(() => {
    if (questionnaireSections.length > 0 && !questionnaireSections.some((section) => section.key === activeQuestionnaireSection)) {
      setActiveQuestionnaireSection(questionnaireSections[0].key);
    }
  }, [questionnaireSections, activeQuestionnaireSection]);

  useEffect(() => {
    const section = questionnaireSections.find((item) => item.key === activeQuestionnaireSection);
    if (!section) {
      setActiveQuestionnaireSubgroup('__all__');
      return;
    }

    const subgroupExists =
      activeQuestionnaireSubgroup === '__all__' ||
      (activeQuestionnaireSubgroup === '__parent__' && hasParentQuestions) ||
      section.children.some((child) => child.key === activeQuestionnaireSubgroup);

    if (!subgroupExists) {
      setActiveQuestionnaireSubgroup('__all__');
    }
  }, [questionnaireSections, activeQuestionnaireSection, activeQuestionnaireSubgroup, hasParentQuestions]);

  useEffect(() => {
    setCampaignTypeFilter('all');
    setCampaignSyncFilter('all');
  }, [activeCampaignId]);
  useEffect(() => {
    if (collecteListMode !== 'pending') {
      if (Object.keys(selectedPendingFormIds).length > 0) {
        setSelectedPendingFormIds({});
      }
      return;
    }

    const pendingIds = new Set(
      filteredServiceCollecteForms
        .filter((item) => item.status === 'pending')
        .map((item) => item.id),
    );

    setSelectedPendingFormIds((prev) => {
      const next: Record<string, boolean> = {};
      for (const id of Object.keys(prev)) {
        if (prev[id] && pendingIds.has(id)) {
          next[id] = true;
        }
      }
      const prevKeys = Object.keys(prev).filter((key) => prev[key]).sort();
      const nextKeys = Object.keys(next).filter((key) => next[key]).sort();
      if (prevKeys.length === nextKeys.length && prevKeys.every((key, index) => key === nextKeys[index])) {
        return prev;
      }
      return next;
    });
  }, [collecteListMode, filteredServiceCollecteForms, selectedPendingFormIds]);

  const selectedProvince = useMemo(
    () => referenceProvinces.find((item) => String(item.id) === selectedProvinceId) ?? null,
    [referenceProvinces, selectedProvinceId],
  );
  const selectedTerritoire = useMemo(
    () => referenceTerritoires.find((item) => String(item.id) === selectedTerritoireId) ?? null,
    [referenceTerritoires, selectedTerritoireId],
  );
  const selectedCommune = useMemo(
    () => referenceCommunes.find((item) => String(item.id) === selectedCommuneId) ?? null,
    [referenceCommunes, selectedCommuneId],
  );

  const filteredTerritoires = useMemo(() => {
    if (!selectedProvinceId) {
      return [];
    }
    return referenceTerritoires.filter((territoire) => String(territoire.province_id ?? '') === selectedProvinceId);
  }, [referenceTerritoires, selectedProvinceId]);

  const filteredCommunes = useMemo(() => {
    if (!selectedTerritoireId) {
      return [];
    }
    return referenceCommunes.filter((commune) => String(commune.territoire_id ?? '') === selectedTerritoireId);
  }, [referenceCommunes, selectedTerritoireId]);

  const filteredSites = useMemo(() => {
    const dedupeById = (sites: SiteRef[]) => {
      const mapped = new Map<number, SiteRef>();
      for (const site of sites) {
        mapped.set(Number(site.id), site);
      }
      return Array.from(mapped.values());
    };

    if (selectedCommuneId) {
      const byCommuneId = referenceSites.filter((site) => String(site.commune_id ?? '') === selectedCommuneId);
      const communeName = normalizeLookupValue(selectedCommune?.name);
      const byZoneSanteName = communeName === ''
        ? []
        : referenceSites.filter((site) => normalizeLookupValue(site.zone_sante) === communeName);

      return dedupeById([...byCommuneId, ...byZoneSanteName]);
    }

    if (selectedTerritoireId) {
      const communeIdsInTerritoire = new Set(
        referenceCommunes
          .filter((commune) => String(commune.territoire_id ?? '') === selectedTerritoireId)
          .map((commune) => Number(commune.id)),
      );
      const byCommuneInTerritoire = referenceSites.filter(
        (site) => site.commune_id !== null && site.commune_id !== undefined && communeIdsInTerritoire.has(Number(site.commune_id)),
      );
      const territoireName = normalizeLookupValue(selectedTerritoire?.name);
      const byTerritoireName = territoireName === ''
        ? []
        : referenceSites.filter((site) => normalizeLookupValue(site.territoire) === territoireName);

      return dedupeById([...byCommuneInTerritoire, ...byTerritoireName]);
    }

    if (selectedProvinceId) {
      const territoireIdsInProvince = new Set(
        referenceTerritoires
          .filter((territoire) => String(territoire.province_id ?? '') === selectedProvinceId)
          .map((territoire) => Number(territoire.id)),
      );
      const communeIdsInProvince = new Set(
        referenceCommunes
          .filter(
            (commune) =>
              territoireIdsInProvince.has(Number(commune.territoire_id ?? 0)) ||
              String(commune.province_id ?? '') === selectedProvinceId,
          )
          .map((commune) => Number(commune.id)),
      );
      const byCommuneInProvince = referenceSites.filter(
        (site) => site.commune_id !== null && site.commune_id !== undefined && communeIdsInProvince.has(Number(site.commune_id)),
      );
      const provinceName = normalizeLookupValue(selectedProvince?.name);
      const byProvinceName = provinceName === ''
        ? []
        : referenceSites.filter((site) => normalizeLookupValue(site.province) === provinceName);

      return dedupeById([...byCommuneInProvince, ...byProvinceName]);
    }

    return [];
  }, [
    referenceSites,
    referenceCommunes,
    referenceTerritoires,
    selectedCommuneId,
    selectedCommune,
    selectedTerritoireId,
    selectedTerritoire,
    selectedProvinceId,
    selectedProvince,
  ]);

  useEffect(() => {
    if (selectedProvinceId && !referenceProvinces.some((item) => String(item.id) === selectedProvinceId)) {
      setSelectedProvinceId('');
    }
  }, [selectedProvinceId, referenceProvinces]);

  useEffect(() => {
    if (selectedTerritoireId && !filteredTerritoires.some((item) => String(item.id) === selectedTerritoireId)) {
      setSelectedTerritoireId('');
    }
  }, [selectedTerritoireId, filteredTerritoires]);

  useEffect(() => {
    if (selectedCommuneId && !filteredCommunes.some((item) => String(item.id) === selectedCommuneId)) {
      setSelectedCommuneId('');
    }
  }, [selectedCommuneId, filteredCommunes]);

  useEffect(() => {
    if (siteId && !filteredSites.some((item) => String(item.id) === siteId)) {
      setSiteId('');
    }
  }, [siteId, filteredSites]);

  useEffect(() => {
    if (siteSelectionMode !== 'existing') {
      setExistingSitePoint(null);
      setExistingSitePolygonPoints([]);
      return;
    }

    const selectedSiteId = Number(siteId || 0);
    if (!selectedSiteId || Number.isNaN(selectedSiteId)) {
      setExistingSitePoint(null);
      setExistingSitePolygonPoints([]);
      return;
    }

    const latestLocalGeo = safeQueue
      .filter((item) => item.type === 'geography' && Number(item.site_id) === selectedSiteId && item.payload)
      .sort((left, right) => String(right.created_at || '').localeCompare(String(left.created_at || '')))[0];

    if (latestLocalGeo) {
      const parsedLocal = parseGeometryFromGeojson(latestLocalGeo.payload?.geojson);
      if (parsedLocal.geometryType === 'polygon' && parsedLocal.polygon.length > 0) {
        setExistingSitePolygonPoints(parsedLocal.polygon);
        setExistingSitePoint(null);
        setMapRegion({
          latitude: parsedLocal.polygon[0].latitude,
          longitude: parsedLocal.polygon[0].longitude,
          latitudeDelta: 0.02,
          longitudeDelta: 0.02,
        });
        return;
      }
      if (parsedLocal.geometryType === 'point' && parsedLocal.point) {
        setExistingSitePoint(parsedLocal.point);
        setExistingSitePolygonPoints([]);
        setMapRegion({
          latitude: parsedLocal.point.latitude,
          longitude: parsedLocal.point.longitude,
          latitudeDelta: 0.02,
          longitudeDelta: 0.02,
        });
        return;
      }
    }

    const selectedSite = referenceSites.find((site) => Number(site.id) === selectedSiteId);
    if (!selectedSite) {
      setExistingSitePoint(null);
      setExistingSitePolygonPoints([]);
      return;
    }

    let parsedSiteGeojson: unknown = selectedSite.geojson_data ?? null;
    if (typeof parsedSiteGeojson === 'string') {
      try {
        parsedSiteGeojson = JSON.parse(parsedSiteGeojson);
      } catch (_error) {
        parsedSiteGeojson = null;
      }
    }

    const parsedSite = parseGeometryFromGeojson(parsedSiteGeojson);
    if (parsedSite.geometryType === 'polygon' && parsedSite.polygon.length > 0) {
      setExistingSitePolygonPoints(parsedSite.polygon);
      setExistingSitePoint(null);
      setMapRegion({
        latitude: parsedSite.polygon[0].latitude,
        longitude: parsedSite.polygon[0].longitude,
        latitudeDelta: 0.02,
        longitudeDelta: 0.02,
      });
      return;
    }
    if (parsedSite.geometryType === 'point' && parsedSite.point) {
      setExistingSitePoint(parsedSite.point);
      setExistingSitePolygonPoints([]);
      setMapRegion({
        latitude: parsedSite.point.latitude,
        longitude: parsedSite.point.longitude,
        latitudeDelta: 0.02,
        longitudeDelta: 0.02,
      });
      return;
    }

    const latitude = Number(selectedSite.latitude ?? NaN);
    const longitude = Number(selectedSite.longitude ?? NaN);
    if (!Number.isNaN(latitude) && !Number.isNaN(longitude)) {
      const fallbackPoint = { latitude, longitude };
      setExistingSitePoint(fallbackPoint);
      setExistingSitePolygonPoints([]);
      setMapRegion({
        latitude: fallbackPoint.latitude,
        longitude: fallbackPoint.longitude,
        latitudeDelta: 0.02,
        longitudeDelta: 0.02,
      });
      return;
    }

    setExistingSitePoint(null);
    setExistingSitePolygonPoints([]);
  }, [siteSelectionMode, siteId, safeQueue, referenceSites]);

  const refreshQueue = () => setQueue(readReconciledStoredForms());
  const refreshCampaigns = useCallback(() => {
    if (!user) {
      setCampaigns([]);
      setActiveCampaignId(null);
      return;
    }

    const nextCampaigns = readCampaignsByUser(user.id);
    setCampaigns(nextCampaigns);
    const nextActiveCampaigns = nextCampaigns.filter((campaign) => !campaign.is_archived);
    if (nextActiveCampaigns.length === 0) {
      setActiveCampaignId(null);
      return;
    }

    if (!activeCampaignId || !nextActiveCampaigns.some((campaign) => campaign.id === activeCampaignId)) {
      setActiveCampaignId(nextActiveCampaigns[0].id);
    }
  }, [user, activeCampaignId]);

  useEffect(() => {
    refreshCampaigns();
  }, [refreshCampaigns]);

  const updateQuestionnaireField = (key: string, value: string) => {
    if (openedSavedFormStatus === 'pending') {
      return;
    }
    setQuestionnaireAnswers((prev) => ({ ...prev, [key]: value }));
    setInvalidRequiredQuestionKeys((prev) => {
      if (!prev[key]) {
        return prev;
      }
      const next = { ...prev };
      delete next[key];
      return next;
    });
  };

  const updateNewSiteField = (key: string, value: string) => {
    setNewSiteData((prev) => ({ ...prev, [key]: value }));
  };

  const hasValidNewSite = useMemo(() => {
    const name = String(newSiteData.nom ?? '').trim();
    return name.length > 0;
  }, [newSiteData]);

  useEffect(() => {
    const hasValidExistingSite = String(siteId || '').trim().length > 0 && Number(siteId) > 0;
    if ((siteSelectionMode === 'existing' ? hasValidExistingSite : hasValidNewSite) && collecteMetaErrors.site) {
      setCollecteMetaErrors((prev) => ({ ...prev, site: false }));
    }
  }, [siteId, siteSelectionMode, hasValidNewSite, collecteMetaErrors.site]);

  useEffect(() => {
    if (isValidCollecteDate(dateCollecte) && collecteMetaErrors.date) {
      setCollecteMetaErrors((prev) => ({ ...prev, date: false }));
    }
  }, [dateCollecte, collecteMetaErrors.date]);

  useEffect(() => {
    if (!activeCampaign) {
      return;
    }

    setSiteId(String(activeCampaign.site_id));
    setCampaignPeriodInput(activeCampaign.period_mm_yyyy);
    setDateCollecte(periodToCollecteDate(activeCampaign.period_mm_yyyy));
  }, [activeCampaign]);

  const handleCreateCampaign = (openCollectForm = true, targetSectionKey?: string) => {
    if (!user) {
      Alert.alert('Connexion requise', 'Connectez-vous avant de créer une campagne.');
      return;
    }

    const siteNumber = Number(activeCampaign?.site_id ?? siteId);
    if (!siteNumber || Number.isNaN(siteNumber)) {
      Alert.alert('Site requis', 'Sélectionnez un site valide pour créer la campagne.');
      return;
    }

    const period = String(campaignPeriodInput || '').trim();
    if (!isValidPeriod(period)) {
      Alert.alert('Période invalide', 'Utilisez le format MM-AAAA, par exemple 08-2026.');
      return;
    }

    const selectedSite = referenceSites.find((site) => Number(site.id) === siteNumber);
    const siteLabel = selectedSite
      ? `${String(selectedSite.nom ?? `Site ${siteNumber}`)}${selectedSite.code_site ? ` (${selectedSite.code_site})` : ''}`
      : `Site ${siteNumber}`;

    const campaignId = createCampaign(user.id, siteNumber, siteLabel, period);
    if (!campaignId) {
      Alert.alert('Erreur', 'Impossible de créer la campagne.');
      return;
    }

    refreshCampaigns();
    setActiveCampaignId(campaignId);
    setDateCollecte(periodToCollecteDate(period));
    if (targetSectionKey) {
      if (questionnaire) {
        setActiveQuestionnaireSection(targetSectionKey);
        setActiveQuestionnaireSubgroup('__all__');
      } else {
        setSelectedSector(targetSectionKey as SectorKey);
      }
    }
    setCampaignPanelMode('standard');
    if (openCollectForm) {
      setActiveTab('collecte');
      setTab('collecte_form');
    }
    Alert.alert('Campagne créée', `Campagne ${period} créée pour ${siteLabel}.`);
  };

  const handleUpdateActiveCampaign = () => {
    if (!user || !activeCampaign) {
      Alert.alert('Campagne requise', 'Sélectionnez une campagne à modifier.');
      return;
    }

    const linkedForms = safeQueue.filter((item) => Number(item.campaign_id ?? 0) === activeCampaign.id);
    const hasSynced = linkedForms.some((item) => item.status === 'synced');
    if (hasSynced) {
      Alert.alert(
        'Modification bloquée',
        'Cette campagne contient déjà des formulaires synchronisés. Pour la sécurité des historiques, modification interdite.',
      );
      return;
    }

    const siteNumber = Number(siteId);
    if (!siteNumber || Number.isNaN(siteNumber)) {
      Alert.alert('Site requis', 'Sélectionnez un site valide.');
      return;
    }

    const period = String(campaignPeriodInput || '').trim();
    if (!isValidPeriod(period)) {
      Alert.alert('Période invalide', 'Utilisez le format MM-AAAA.');
      return;
    }

    const selectedSite = referenceSites.find((site) => Number(site.id) === siteNumber);
    const siteLabel = selectedSite
      ? `${String(selectedSite.nom ?? `Site ${siteNumber}`)}${selectedSite.code_site ? ` (${selectedSite.code_site})` : ''}`
      : `Site ${siteNumber}`;

    Alert.alert(
      'Confirmer la modification',
      `Mettre à jour la campagne ${activeCampaign.period_mm_yyyy} vers ${period} ?`,
      [
        { text: 'Annuler', style: 'cancel' },
        {
          text: 'Confirmer',
          style: 'default',
          onPress: () => {
            updateCampaign(activeCampaign.id, siteNumber, siteLabel, period);
            updatePendingFormsForCampaign(activeCampaign.id, siteNumber, period);
            refreshQueue();
            refreshCampaigns();
            setDateCollecte(periodToCollecteDate(period));
            Alert.alert('Campagne mise à jour', 'Les brouillons liés ont été alignés sur la nouvelle période/site.');
          },
        },
      ],
    );
  };

  const handleArchiveActiveCampaign = () => {
    if (!activeCampaign) {
      Alert.alert('Campagne requise', 'Sélectionnez une campagne à archiver.');
      return;
    }

    const linkedForms = safeQueue.filter((item) => Number(item.campaign_id ?? 0) === activeCampaign.id);
    const pendingCount = linkedForms.filter((item) => item.status === 'pending').length;
    const syncedCount = linkedForms.filter((item) => item.status === 'synced').length;

    if (pendingCount > 0) {
      Alert.alert(
        'Archivage bloqué',
        'Cette campagne contient des formulaires en attente. Synchronisez-les avant archivage.',
      );
      return;
    }

    Alert.alert(
      'Archiver la campagne',
      `Archiver la campagne ${activeCampaign.period_mm_yyyy} ?\nHistorique local conservé (${syncedCount} formulaire(s) synchronisé(s)).`,
      [
        { text: 'Annuler', style: 'cancel' },
        {
          text: 'Archiver',
          style: 'default',
          onPress: () => {
            archiveCampaign(activeCampaign.id);
            refreshQueue();
            refreshCampaigns();
            Alert.alert('Campagne archivée', 'La campagne est masquée des campagnes actives, sans perte de l’historique.');
          },
        },
      ],
    );
  };

  const getQuestionChoices = (question: QuestionnaireQuestion): QuestionnaireChoice[] => {
    const listName = extractQuestionListName(question);
    if (!listName || !questionnaire?.choices) {
      return [];
    }

    const matched = questionnaire.choices.filter((choice) => String(choice.list_name ?? choice.listName ?? '').trim().toLowerCase() === listName);
    const choiceFilter = String(question.choice_filter ?? '').trim();
    if (!choiceFilter) {
      return matched;
    }

    const conditions = [...choiceFilter.matchAll(/([a-zA-Z0-9_]+)\s*=\s*\$\{([^}]+)\}/g)];
    if (conditions.length === 0) {
      return matched;
    }

    return matched.filter((choice) =>
      conditions.every((condition) => {
        const choiceKey = condition[1];
        const answerKey = condition[2];
        const answerValue = String(questionnaireAnswers[answerKey] ?? '').trim();
        if (!answerValue) {
          return false;
        }
        return String((choice as Record<string, string | undefined>)[choiceKey] ?? '').trim() === answerValue;
      }),
    );
  };

  const getCurrentLocation = async (): Promise<Point | null> => {
    try {
      const { status } = await Location.requestForegroundPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('Permission GPS', 'La localisation est nécessaire pour collecter les points et les polygones.');
        return null;
      }

      const current = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
      const nextPoint = {
        latitude: current.coords.latitude,
        longitude: current.coords.longitude,
      };
      setPoint(nextPoint);
      setMapRegion({
        latitude: nextPoint.latitude,
        longitude: nextPoint.longitude,
        latitudeDelta: 0.02,
        longitudeDelta: 0.02,
      });
      return nextPoint;
    } catch (error) {
      console.warn('Location fetch failed', error);
      return null;
    }
  };

  const handleLogin = async () => {
    const normalizedEmail = email.trim();
    const normalizedPassword = password.trim();

    if (!normalizedEmail || !normalizedPassword) {
      Alert.alert('Connexion', 'Saisissez votre email et mot de passe.');
      return;
    }

    try {
      const response = await fetch(`${apiBase}/api/mobile/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ email: normalizedEmail, password: normalizedPassword }),
      });

      const payload = await response.json();
      if (!response.ok || !payload?.success) {
        throw new Error(payload?.message || 'Connexion impossible.');
      }

      const nextUser: User = payload.user;
      setUser(nextUser);
      writeStoredSession(nextUser);
      Alert.alert('Connexion', payload.message || 'Connecté.');
    } catch (error) {
      Alert.alert('Erreur de connexion', error instanceof Error ? error.message : 'Identifiants invalides.');
    }
  };

  const saveServerAddress = () => {
    const normalized = normalizeServerBaseUrl(serverBaseUrl);
    if (!normalized.startsWith('http://') && !normalized.startsWith('https://')) {
      Alert.alert('Adresse serveur', 'Utilisez une URL complète, par ex: http://192.168.1.20:8080');
      return;
    }
    writeMobileSetting('api_base', normalized);
    setServerBaseUrl(normalized);
    Alert.alert('Adresse serveur', 'Adresse enregistrée sur le mobile.');
  };

  const testServerAddress = async () => {
    const normalized = normalizeServerBaseUrl(serverBaseUrl) || DEFAULT_API_BASE;
    try {
      const response = await fetch(`${normalized}/api/mobile/questionnaires/active?code=service-cartography&t=${Date.now()}`, {
        headers: {
          Accept: 'application/json',
          'Cache-Control': 'no-cache',
        },
      });
      Alert.alert(
        'Test réseau',
        response.ok
          ? `Connexion OK avec ${normalized}`
          : `Serveur joignable (${response.status}) via ${normalized}`,
      );
    } catch (error) {
      Alert.alert(
        'Test réseau',
        `Connexion échouée vers ${normalized}. Vérifiez IP/port du serveur et le pare-feu.`,
      );
    }
  };

  const handleLogout = () => {
    clearStoredSession();
    setUser(null);
    setCampaigns([]);
    setActiveCampaignId(null);
    setActiveTab('dashboard');
  };

  const openReadySync = (origin: ReadySyncOrigin) => {
    setReadySyncOrigin(origin);
    setActiveTab('sync');
    setTab('ready_sync');
  };

  const closeReadySync = () => {
    if (readySyncOrigin === 'movement') {
      setActiveTab('collecte');
      setMovementPanelMode('list');
      setMovementListMode('all');
      setTab('movement');
      return;
    }

    if (readySyncOrigin === 'geography') {
      setActiveTab('geography');
      setGeographyPanelMode('list');
      setGeographyListMode('all');
      setTab('geography');
      return;
    }

    if (readySyncOrigin === 'collecte') {
      setActiveTab('collecte');
      setCampaignPanelMode('standard');
      setTab('campaign');
      return;
    }

    setActiveTab('dashboard');
    setTab('dashboard');
  };

  const handleBottomNav = async (key: 'dashboard' | 'collecte' | 'geography' | 'sync' | 'profile') => {
    setActiveTab(key);

    if (key === 'dashboard') {
      setTab('dashboard');
      return;
    }

    if (key === 'collecte') {
      setTab('sector');
      return;
    }

    if (key === 'geography') {
      setGeographyPanelMode('list');
      setGeographyListMode('all');
      setTab('geography');
      return;
    }

    if (key === 'sync') {
      openReadySync('dashboard');
      return;
    }

    if (key === 'profile') {
      setTab('dashboard');
    }
  };

  const openAction = (actionKey: string) => {
    if (actionKey === 'Thématique') {
      setActiveTab('collecte');
      setTab('sector');
      return;
    }

    if (actionKey === 'Géographie') {
      setActiveTab('geography');
      setGeographyPanelMode('list');
      setTab('geography');
      return;
    }

    if (actionKey === 'Mouvements') {
      refreshQueue();
      setActiveTab('collecte');
      setMovementPanelMode('list');
      setMovementListMode('all');
      setTab('movement');
      return;
    }

    if (actionKey === 'OSSAT') {
      setActiveTab('collecte');
      setTab('ossat');
      return;
    }

    if (actionKey === 'Photos') {
      void addPhoto();
      return;
    }

    if (actionKey === 'Synchroniser') {
      openReadySync('dashboard');
      return;
    }

    if (actionKey === 'File locale') {
      setActiveTab('collecte');
      setTab('sector');
    }
  };

  const openCollecteAction = (actionKey: string) => {
    if (actionKey === 'Nouvelle campagne') {
      setActiveTab('collecte');
      setTab('campaign');
      setCampaignPanelMode('menu');
      return;
    }

    if (actionKey === 'Nouvelle collecte') {
      setActiveTab('collecte');
      setTab('campaign');
      setCampaignPanelMode('standard');
      setCollecteListMode('none');
      setOpenedSavedFormStatus(null);
      setEditingFormId(null);
      setEditingFormCreatedAt(null);
      setCampaignSyncFilter('all');
      return;
    }

    if (actionKey === 'Brouillons') {
      if (!serviceCollecteForms.some((item) => item.status === 'draft')) {
        Alert.alert('Brouillons', 'Aucun brouillon disponible.');
      }
      setActiveTab('collecte');
      setTab('campaign');
      setCampaignPanelMode('standard');
      setCollecteListMode('draft');
      setCampaignSyncFilter('all');
      return;
    }

    if (actionKey === 'Pret a envoyer') {
      openReadySync('collecte');
      return;
    }

    if (actionKey === 'Sync') {
      if (!serviceCollecteForms.some((item) => item.status === 'synced')) {
        Alert.alert('Sync', 'Aucun formulaire déjà synchronisé.');
      }
      setActiveTab('collecte');
      setTab('campaign');
      setCampaignPanelMode('standard');
      setCollecteListMode('synced');
      setCampaignSyncFilter('synced');
    }
  };

  const openGeographyAction = (actionKey: string) => {
    if (actionKey === 'Nouvelle collecte géographique') {
      startNewGeographyCollect();
      return;
    }

    if (actionKey === 'Brouillons') {
      if (!geographyForms.some((item) => item.status === 'draft')) {
        Alert.alert('Brouillons', 'Aucun brouillon géographique disponible.');
      }
      setGeographyListMode('draft');
      return;
    }

    if (actionKey === 'Pret a envoyer') {
      openReadySync('geography');
      return;
    }

    if (actionKey === 'Sync') {
      if (!geographyForms.some((item) => item.status === 'synced')) {
        Alert.alert('Sync', 'Aucun formulaire géographique déjà synchronisé.');
      }
      setGeographyListMode('synced');
    }
  };

  const openMovementAction = (actionKey: string) => {
    if (actionKey === 'Nouveau mouvement') {
      startNewMovement();
      return;
    }
    if (actionKey === 'Brouillons') {
      setMovementListMode('draft');
      return;
    }
    if (actionKey === 'Pret a envoyer') {
      openReadySync('movement');
      return;
    }
    if (actionKey === 'À corriger') {
      refreshQueue();
      setMovementListMode('correction');
      return;
    }
    if (actionKey === 'Synchronisés') {
      setMovementListMode('synced');
    }
  };

  const addPhoto = async () => {
    const uri = await pickPhoto();
    if (uri) {
      setPhotos((prev) => [...prev, uri]);
    }
  };

  const updateSectorField = (key: string, value: string) => {
    if (openedSavedFormStatus === 'pending') {
      return;
    }
    setSectorData((prev) => ({ ...prev, [key]: value }));
  };

  const updateOssatField = (key: string, value: string) => {
    setOssatData((prev) => ({ ...prev, [key]: value }));
  };

  const fillRandomCollecteData = () => {
    if (openedSavedFormStatus === 'pending') {
      Alert.alert('Lecture seule', 'Ce formulaire prêt à envoyer ne peut pas être modifié.');
      return;
    }
    if (questionnaire && activeQuestionnaireQuestions.length > 0) {
      const randomAnswers: Record<string, string> = {};
      for (const question of activeQuestionnaireQuestions) {
        const questionType = String(question.type || '').trim().toLowerCase();
        const questionName = String(question.name || '').trim();
        if (!questionName || questionType === 'note') {
          continue;
        }

        const isSelectOne = /^select[_ ]one\b/.test(questionType);
        const isSelectMultiple = /^select[_ ]multiple\b/.test(questionType);
        if (isSelectOne || isSelectMultiple) {
          const choices = getQuestionChoices(question);
          const normalizedChoices = choices.map((choice) => ({
            value: String(choice.name || '').trim(),
            label: getChoiceLabel(choice) || String(choice.name || '').trim(),
          })).filter((choice) => choice.value.length > 0);
          if (normalizedChoices.length === 0) {
            continue;
          }

          if (isSelectOne) {
            const selected = normalizedChoices[Math.floor(Math.random() * normalizedChoices.length)];
            randomAnswers[questionName] = selected.value;
            if (isOtherChoiceLabel(selected.value, selected.label)) {
              randomAnswers[`${questionName}__autre`] = `Autre ${Math.floor(Math.random() * 999) + 1}`;
            } else {
              randomAnswers[`${questionName}__autre`] = '';
            }
            continue;
          }

          const maxCount = Math.min(3, normalizedChoices.length);
          const targetCount = Math.max(1, Math.floor(Math.random() * maxCount) + 1);
          const shuffled = [...normalizedChoices].sort(() => Math.random() - 0.5).slice(0, targetCount);
          randomAnswers[questionName] = shuffled.map((item) => item.value).join(' ');
          const hasOther = shuffled.some((item) => isOtherChoiceLabel(item.value, item.label));
          randomAnswers[`${questionName}__autre`] = hasOther ? `Autre ${Math.floor(Math.random() * 999) + 1}` : '';
          continue;
        }

        if (questionType.includes('int')) {
          randomAnswers[questionName] = String(Math.floor(Math.random() * 200) + 1);
          continue;
        }

        if (questionType.includes('decimal')) {
          randomAnswers[questionName] = (Math.random() * 100).toFixed(1);
          continue;
        }

        if (questionType === 'date') {
          randomAnswers[questionName] = formatDate(new Date());
          continue;
        }

        randomAnswers[questionName] = `Test ${Math.floor(Math.random() * 999) + 1}`;
      }

      setQuestionnaireAnswers((prev) => ({ ...prev, ...randomAnswers }));
      Alert.alert('Test', 'Le thème courant a été rempli aléatoirement.');
      return;
    }

    const randomSectorValues: Record<string, string> = {};
    for (const field of sectorFields) {
      if (field.type === 'boolean') {
        randomSectorValues[field.key] = Math.random() > 0.5 ? 'Oui' : 'Non';
      } else if (field.type === 'number') {
        randomSectorValues[field.key] = String(Math.floor(Math.random() * 200) + 1);
      } else {
        randomSectorValues[field.key] = `Test ${Math.floor(Math.random() * 999) + 1}`;
      }
    }
    setSectorData((prev) => ({ ...prev, ...randomSectorValues }));
    Alert.alert('Test', 'La thématique courante a été remplie aléatoirement.');
  };

  const addPointFromLocation = () => {
    if (!point) {
      void getCurrentLocation();
      return;
    }

    if (isPolygonMode) {
      setPolygonPoints((prev) => {
        if (prev.length === 0) {
          return [point];
        }
        const lastPoint = prev[prev.length - 1];
        if (distanceBetweenPointsMeters(lastPoint, point) < 2) {
          return prev;
        }
        return [...prev, point];
      });
      return;
    }

    setPoint(point);
  };

  const addSelectedMapPoint = (event: any) => {
    const coordinate = event.nativeEvent.coordinate;
    const nextPoint = {
      latitude: coordinate.latitude,
      longitude: coordinate.longitude,
    };

    if (isPolygonMode) {
      setPolygonPoints((prev) => {
        if (prev.length === 0) {
          return [nextPoint];
        }
        const lastPoint = prev[prev.length - 1];
        if (distanceBetweenPointsMeters(lastPoint, nextPoint) < 2) {
          return prev;
        }
        return [...prev, nextPoint];
      });
      return;
    }

    setPoint(nextPoint);
  };

  const clearPolygon = () => {
    setPolygonPoints([]);
  };

  const removeLastPolygonBorne = () => {
    setPolygonPoints((prev) => (prev.length > 0 ? prev.slice(0, -1) : prev));
  };

  const startPolygonTracking = async () => {
    if (!isPolygonMode) {
      Alert.alert('Mode GPS', 'Le suivi automatique est disponible uniquement en mode polygone.');
      return;
    }

    if (polygonTrackingSubscriptionRef.current) {
      return;
    }

    try {
      const { status } = await Location.requestForegroundPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('Permission GPS', 'Autorisez la localisation pour suivre votre déplacement.');
        return;
      }

      const seedPoint = await getCurrentLocation();
      if (seedPoint) {
        setPolygonPoints((prev) => (prev.length === 0 ? [seedPoint] : prev));
      }

      const subscription = await Location.watchPositionAsync(
        {
          accuracy: Location.Accuracy.BestForNavigation,
          timeInterval: 3000,
          distanceInterval: 5,
        },
        (location) => {
          const nextPoint: Point = {
            latitude: location.coords.latitude,
            longitude: location.coords.longitude,
          };

          setPoint(nextPoint);
          setMapRegion({
            latitude: nextPoint.latitude,
            longitude: nextPoint.longitude,
            latitudeDelta: 0.01,
            longitudeDelta: 0.01,
          });
          setErrorMargin(String(Math.round(location.coords.accuracy ?? 0)));
        },
      );

      polygonTrackingSubscriptionRef.current = subscription;
      setIsPolygonTracking(true);
    } catch (error) {
      console.warn('Polygon tracking failed', error);
      stopPolygonTracking();
      Alert.alert('GPS', 'Impossible de démarrer le suivi de déplacement.');
    }
  };

  const startNewGeographyCollect = () => {
    stopPolygonTracking();
    setEditingGeographyFormId(null);
    setEditingGeographyCreatedAt(null);
    setSiteId('');
    setSelectedProvinceId('');
    setSelectedTerritoireId('');
    setSelectedCommuneId('');
    setDateCollecte(formatDate(new Date()));
    setGeometryType('polygon');
    setSiteSelectionMode('existing');
    setNewSiteData(INITIAL_NEW_SITE_FORM);
    setGpsPointCategory('');
    setGpsPointOtherLabel('');
    setGpsPolygonCategory('');
    setGpsPolygonBlockName('');
    setExistingSitePoint(null);
    setExistingSitePolygonPoints([]);
    setPoint(null);
    setPolygonPoints([]);
    setErrorMargin('10');
    setGeographyListMode('all');
    setGeographyPanelMode('form');
  };

  const loadGeographyForEditing = (record: FormRecord) => {
    stopPolygonTracking();
    if (record.type !== 'geography' || record.status === 'synced') {
      return;
    }

    const payload = record.payload ?? {};
    setEditingGeographyFormId(record.id);
    setEditingGeographyCreatedAt(record.created_at);
    setSiteId(String(payload.site_id ?? record.site_id ?? ''));
    setSelectedProvinceId(payload.province_id != null ? String(payload.province_id) : '');
    setSelectedTerritoireId(payload.territoire_id != null ? String(payload.territoire_id) : '');
    setSelectedCommuneId(payload.commune_id != null ? String(payload.commune_id) : '');
    const isNewSiteRecord = Boolean(payload?.is_new_site) || (payload?.new_site && typeof payload.new_site === 'object');
    setSiteSelectionMode(isNewSiteRecord ? 'new' : 'existing');
    if (isNewSiteRecord && payload?.new_site && typeof payload.new_site === 'object') {
      const nextNewSite = { ...INITIAL_NEW_SITE_FORM };
      Object.entries(payload.new_site as Record<string, unknown>).forEach(([key, value]) => {
        nextNewSite[key] = String(value ?? '');
      });
      setNewSiteData(nextNewSite);
    } else {
      setNewSiteData(INITIAL_NEW_SITE_FORM);
    }
    setDateCollecte(String(payload.date_collecte ?? dateCollecte));
    setGeometryType(payload.geometry_type === 'point' ? 'point' : 'polygon');
    setErrorMargin(String(payload.accuracy_meters ?? '10'));
    const geojson = payload.geojson;
    const featureProps = geojson?.features?.[0]?.properties ?? {};
    const resolvedPointCategory = String(payload.point_category ?? featureProps.point_category ?? '').trim();
    const resolvedPointOther = String(payload.point_category_other ?? featureProps.point_category_other ?? '').trim();
    const resolvedPolygonCategory = String(payload.polygon_category ?? featureProps.polygon_category ?? '').trim();
    const resolvedPolygonBlockName = String(payload.polygon_block_name ?? featureProps.polygon_block_name ?? '').trim();
    setGpsPointCategory(
      GPS_POINT_CATEGORIES.some((option) => option.value === resolvedPointCategory)
        ? (resolvedPointCategory as GpsPointCategory)
        : '',
    );
    setGpsPointOtherLabel(resolvedPointOther);
    setGpsPolygonCategory(
      GPS_POLYGON_CATEGORIES.some((option) => option.value === resolvedPolygonCategory)
        ? (resolvedPolygonCategory as GpsPolygonCategory)
        : '',
    );
    setGpsPolygonBlockName(resolvedPolygonBlockName);
    if (geojson?.features?.[0]?.geometry?.type === 'Point') {
      const coords = geojson.features[0].geometry.coordinates;
      if (Array.isArray(coords) && coords.length >= 2) {
        const loadedPoint = { latitude: Number(coords[1]), longitude: Number(coords[0]) };
        setPoint(loadedPoint);
        setPolygonPoints([]);
        setMapRegion({
          latitude: loadedPoint.latitude,
          longitude: loadedPoint.longitude,
          latitudeDelta: 0.02,
          longitudeDelta: 0.02,
        });
      }
    } else if (geojson?.features?.[0]?.geometry?.type === 'Polygon') {
      const coordinates = geojson.features[0].geometry.coordinates?.[0] ?? [];
      const nextPolygonPoints = Array.isArray(coordinates)
        ? coordinates
            .filter((coord: unknown) => Array.isArray(coord) && coord.length >= 2)
            .map((coord: any) => ({ latitude: Number(coord[1]), longitude: Number(coord[0]) }))
        : [];
      setPolygonPoints(nextPolygonPoints);
      setPoint(null);
      if (nextPolygonPoints.length > 0) {
        setMapRegion({
          latitude: nextPolygonPoints[0].latitude,
          longitude: nextPolygonPoints[0].longitude,
          latitudeDelta: 0.02,
          longitudeDelta: 0.02,
        });
      }
    } else {
      const lat = Number(payload.latitude ?? NaN);
      const lon = Number(payload.longitude ?? NaN);
      if (!Number.isNaN(lat) && !Number.isNaN(lon)) {
        const loadedPoint = { latitude: lat, longitude: lon };
        setPoint(loadedPoint);
        setPolygonPoints([]);
        setMapRegion({
          latitude: loadedPoint.latitude,
          longitude: loadedPoint.longitude,
          latitudeDelta: 0.02,
          longitudeDelta: 0.02,
        });
      }
    }

    setGeographyPanelMode('form');
  };

  const startNewMovement = () => {
    setEditingMovementFormId(null);
    setEditingMovementCreatedAt(null);
    setEditingMovementSyncError(null);
    setSiteSelectionMode('existing');
    setSiteId('');
    setSelectedProvinceId('');
    setSelectedTerritoireId('');
    setSelectedCommuneId('');
    setMovementData({
      ...INITIAL_MOVEMENT_DATA,
      date_mouvement: formatDate(new Date()),
      periode: new Date().toISOString().slice(0, 7),
    });
    setMovementListMode('all');
    setMovementPanelMode('form');
  };

  const updateMovementField = (key: string, value: string) => {
    if (key === 'type_mouvement') {
      setMovementData((previous) => ({
        ...previous,
        type_mouvement: value,
        raison_mouvement_id: '',
      }));
      return;
    }
    setMovementData((previous) => ({ ...previous, [key]: value }));
  };

  const loadMovementForEditing = (record: FormRecord) => {
    if (record.type !== 'movement' || (record.status !== 'draft' && record.status !== 'correction')) {
      return;
    }
    const payload = record.payload ?? {};
    setEditingMovementFormId(record.id);
    setEditingMovementCreatedAt(record.created_at);
    setEditingMovementSyncError(record.sync_error ?? null);
    setSiteSelectionMode('existing');
    setSiteId(String(payload.site_id ?? record.site_id ?? ''));
    setSelectedProvinceId(payload.province_id != null ? String(payload.province_id) : '');
    setSelectedTerritoireId(payload.territoire_id != null ? String(payload.territoire_id) : '');
    setSelectedCommuneId(payload.commune_id != null ? String(payload.commune_id) : '');
    setMovementData(
      Object.keys(INITIAL_MOVEMENT_DATA).reduce<Record<string, string>>((next, key) => {
        const value = payload[key] ?? INITIAL_MOVEMENT_DATA[key] ?? '';
        next[key] = payload.type_mouvement === 'depart' && key !== 'type_mouvement'
          && (key === 'menages' || MOVEMENT_DEMOGRAPHIC_FIELDS.some((field) => field.key === key))
          ? String(Math.abs(Number(value)))
          : String(value);
        return next;
      }, {}),
    );
    setMovementPanelMode('form');
  };

  const saveMovementForm = (targetStatus: 'draft' | 'pending') => {
    if (!user) {
      Alert.alert('Connexion requise', 'Connectez-vous pour enregistrer un mouvement.');
      return;
    }

    const siteNumber = Number(siteId);
    if (!siteNumber || Number.isNaN(siteNumber)) {
      Alert.alert('Site requis', 'Sélectionnez un site valide.');
      return;
    }

    const movementType = movementData.type_mouvement as MovementType;
    if (!['arrivee', 'depart', 'recensement', 'ajustement'].includes(movementType)) {
      Alert.alert('Type requis', 'Sélectionnez un type de mouvement.');
      return;
    }

    const movementDate = String(movementData.date_mouvement || '').trim();
    if (!isValidCollecteDate(movementDate)) {
      Alert.alert('Date invalide', 'Indiquez une date au format AAAA-MM-JJ.');
      return;
    }

    const parsedValues: Record<string, number> = {};
    const numericFields = ['menages', ...MOVEMENT_DEMOGRAPHIC_FIELDS.map((field) => field.key)];
    for (const field of numericFields) {
      const rawValue = String(movementData[field] ?? '').trim();
      if (!/^-?\d+$/.test(rawValue)) {
        Alert.alert('Valeur invalide', 'Tous les effectifs doivent être des nombres entiers.');
        return;
      }
      const value = Number.parseInt(rawValue, 10);
      if (movementType !== 'ajustement' && value < 0) {
        Alert.alert('Valeur invalide', 'Utilisez des valeurs positives pour les arrivées, départs et recensements.');
        return;
      }
      parsedValues[field] = value;
    }

    const individuals = MOVEMENT_DEMOGRAPHIC_FIELDS.reduce(
      (total, field) => total + parsedValues[field.key],
      0,
    );
    const normalizeMovementValue = (value: number) => (
      movementType === 'depart' ? -Math.abs(value) : value
    );
    const payload = {
      site_id: siteNumber,
      province_id: selectedProvinceId ? Number(selectedProvinceId) : null,
      territoire_id: selectedTerritoireId ? Number(selectedTerritoireId) : null,
      commune_id: selectedCommuneId ? Number(selectedCommuneId) : null,
      date_mouvement: movementDate,
      type_mouvement: movementType,
      raison_mouvement_id: movementData.raison_mouvement_id
        ? Number(movementData.raison_mouvement_id)
        : null,
      periode: String(movementData.periode || movementDate.slice(0, 7)).trim(),
      menages: normalizeMovementValue(parsedValues.menages),
      individus: normalizeMovementValue(individuals),
      ...MOVEMENT_DEMOGRAPHIC_FIELDS.reduce<Record<string, number>>((values, field) => {
        values[field.key] = normalizeMovementValue(parsedValues[field.key]);
        return values;
      }, {}),
      raison: String(movementData.raison || '').trim() || null,
      description: String(movementData.description || '').trim() || null,
      source: String(movementData.source || 'application_mobile').trim(),
      round: String(movementData.round || '').trim() || null,
    };

    const record: FormRecord = {
      id: editingMovementFormId ?? `movement-${Date.now()}`,
      type: 'movement',
      site_id: siteNumber,
      payload,
      created_at: editingMovementCreatedAt ?? new Date().toISOString(),
      status: targetStatus,
      sync_error: null,
    };

    saveFormToDb(record);
    refreshQueue();
    setEditingMovementFormId(null);
    setEditingMovementCreatedAt(null);
    setEditingMovementSyncError(null);
    setMovementPanelMode('list');
    Alert.alert(
      targetStatus === 'draft' ? 'Brouillon enregistré' : 'Prêt à envoyer',
      targetStatus === 'draft'
        ? 'Le mouvement est conservé hors ligne dans les brouillons.'
        : 'Le mouvement est conservé hors ligne et prêt pour la synchronisation.',
    );
  };

  const openSavedForm = (record: FormRecord) => {
    const payload = record.payload ?? {};
    const payloadDate = String(payload?.date_collecte ?? '').trim();
    const payloadSiteId = Number(payload?.site_id ?? record.site_id ?? 0);
    const payloadProvinceId = payload?.province_id != null ? String(payload.province_id) : '';
    const payloadTerritoireId = payload?.territoire_id != null ? String(payload.territoire_id) : '';
    const payloadCommuneId = payload?.commune_id != null ? String(payload.commune_id) : '';

    setEditingFormId(record.id);
    setEditingFormCreatedAt(record.created_at);
    setOpenedSavedFormStatus(record.status === 'draft' ? 'draft' : 'pending');
    setDateCollecte(payloadDate || formatDate(new Date()));
    setSiteId(payloadSiteId > 0 ? String(payloadSiteId) : '');
    setSelectedProvinceId(payloadProvinceId);
    setSelectedTerritoireId(payloadTerritoireId);
    setSelectedCommuneId(payloadCommuneId);
    const isNewSiteRecord = Boolean(payload?.is_new_site) || (payload?.new_site && typeof payload.new_site === 'object');
    setSiteSelectionMode(isNewSiteRecord ? 'new' : 'existing');
    if (isNewSiteRecord && payload?.new_site && typeof payload.new_site === 'object') {
      const nextNewSite = { ...INITIAL_NEW_SITE_FORM };
      Object.entries(payload.new_site as Record<string, unknown>).forEach(([key, value]) => {
        nextNewSite[key] = String(value ?? '');
      });
      setNewSiteData(nextNewSite);
    } else {
      setNewSiteData(INITIAL_NEW_SITE_FORM);
    }

    if (record.type === 'questionnaire') {
      const answers = payload?.answers && typeof payload.answers === 'object'
        ? (payload.answers as Record<string, unknown>)
        : {};
      const normalizedAnswers = Object.fromEntries(
        Object.entries(answers).map(([key, value]) => [key, String(value ?? '')]),
      );
      setQuestionnaireAnswers(normalizedAnswers);
    } else {
      const sectorValue = String(payload?.sector ?? record?.sector ?? selectedSector).trim();
      if (sectorValue === 'wash' || sectorValue === 'sante' || sectorValue === 'protection' || sectorValue === 'education' || sectorValue === 'environnement' || sectorValue === 'abri') {
        setSelectedSector(sectorValue);
      }
      setSectorData((prev) => ({
        ...prev,
        ...Object.fromEntries(
          Object.entries(payload).map(([key, value]) => [key, String(value ?? '')]),
        ),
      }));
    }

    setCollecteListMode('none');
    setActiveTab('collecte');
    setTab('collecte_form');
    Alert.alert(
      record.status === 'draft' ? 'Brouillon' : 'Prêt à envoyer',
      record.status === 'draft'
        ? 'Brouillon chargé. Vous pouvez le modifier puis enregistrer.'
        : 'Formulaire chargé. Vous pouvez afficher ses éléments.',
    );
  };

  const saveSectorDraft = (targetStatus: 'draft' | 'pending' = 'pending') => {
    if (!user) {
      Alert.alert('Connexion requise', 'Connectez-vous pour enregistrer une collecte.');
      return;
    }
    if (openedSavedFormStatus === 'pending') {
      Alert.alert('Lecture seule', 'Ce formulaire est déjà prêt à envoyer. La modification est bloquée.');
      return;
    }

    const siteValue = String(siteId || '').trim();
    const collecteDate = String(dateCollecte || '').trim();
    const hasValidDate = isValidCollecteDate(collecteDate);
    const existingSiteNumber = Number(siteValue);
    const hasValidExistingSite = siteValue.length > 0 && !Number.isNaN(existingSiteNumber) && existingSiteNumber > 0;
    const hasValidSite = siteSelectionMode === 'existing' ? hasValidExistingSite : hasValidNewSite;
    const siteNumber = siteSelectionMode === 'existing' ? existingSiteNumber : 0;

    if (!hasValidSite || !hasValidDate) {
      setCollecteMetaErrors({ site: !hasValidSite, date: !hasValidDate });
      Alert.alert(
        'Site et date requis',
        siteSelectionMode === 'new'
          ? 'Indiquez au moins le nom du nouveau site et une date de collecte (AAAA-MM-JJ).'
          : 'Indiquez un site valide et une date de collecte (AAAA-MM-JJ) avant de sauvegarder.',
      );
      return;
    }
    const collectePeriod = activeCampaign?.period_mm_yyyy || collecteDateToPeriod(collecteDate);

    if (questionnaire) {
      const missingRequiredQuestions: string[] = [];
      const missingRequiredKeys = new Set<string>();

      for (const question of activeQuestionnaireQuestions) {
        const questionType = String(question.type || '').trim().toLowerCase();
        const questionName = String(question.name || '').trim();
        if (!questionName || questionType === 'note') {
          continue;
        }

        const requiredFlag = String(question.required ?? '').trim().toLowerCase();
        const isRequired = requiredFlag === 'true' || requiredFlag === '1' || requiredFlag === 'yes';
        const value = String(questionnaireAnswers[questionName] ?? '').trim();
        const isSelectMultiple = /^select[_ ]multiple\b/.test(questionType);
        const selectedValues = isSelectMultiple ? parseSelectedValues(value) : [];

        if (isRequired) {
          const isEmpty = isSelectMultiple ? selectedValues.length === 0 : value.length === 0;
          if (isEmpty) {
            missingRequiredQuestions.push(getQuestionLabel(question) || questionName);
            missingRequiredKeys.add(questionName);
            continue;
          }
        }

        const choices = getQuestionChoices(question);
        const hasOtherSelected = (
          (/^select[_ ]one\b/.test(questionType) &&
            choices.some((choice) => {
              const choiceValue = String(choice.name || '').trim();
              const choiceLabel = getChoiceLabel(choice) || choiceValue;
              return choiceValue === value && isOtherChoiceLabel(choiceValue, choiceLabel);
            }))
          ||
          (isSelectMultiple &&
            selectedValues.some((selectedValue) =>
              choices.some((choice) => {
                const choiceValue = String(choice.name || '').trim();
                const choiceLabel = getChoiceLabel(choice) || choiceValue;
                return choiceValue === selectedValue && isOtherChoiceLabel(choiceValue, choiceLabel);
              }),
            ))
        );

        if (hasOtherSelected) {
          const otherDetailKey = `${questionName}__autre`;
          const otherDetailValue = String(questionnaireAnswers[otherDetailKey] ?? '').trim();
          if (!otherDetailValue) {
            missingRequiredQuestions.push(`${getQuestionLabel(question) || questionName} (Précisez autre)`);
            missingRequiredKeys.add(otherDetailKey);
          }
        }
      }

      if (missingRequiredQuestions.length > 0) {
        setInvalidRequiredQuestionKeys(
          Array.from(missingRequiredKeys).reduce<Record<string, boolean>>((acc, key) => {
            acc[key] = true;
            return acc;
          }, {}),
        );
        const preview = missingRequiredQuestions.slice(0, 3).join('\n- ');
        const suffix = missingRequiredQuestions.length > 3 ? `\n… +${missingRequiredQuestions.length - 3} autre(s)` : '';
        Alert.alert(
          'Champs obligatoires manquants',
          `Complétez les éléments obligatoires avant sauvegarde :\n- ${preview}${suffix}`,
        );
        return;
      }
      setInvalidRequiredQuestionKeys({});
    }

    const normalizedNewSitePayload = siteSelectionMode === 'new'
      ? {
          ...newSiteData,
          nom: String(newSiteData.nom ?? '').trim(),
          code_site: String(newSiteData.code_site ?? '').trim(),
          province: selectedProvince?.name || null,
          territoire: selectedTerritoire?.name || null,
          zone_sante: selectedCommune?.name || null,
          commune_id: selectedCommuneId ? Number(selectedCommuneId) : (newSiteData.commune_id ? Number(newSiteData.commune_id) : null),
          type_gestion: String(newSiteData.type_gestion ?? '').trim() || null,
          source: 'mobile',
        }
      : null;

    const payload = questionnaire
      ? {
          questionnaire_code: questionnaire.code,
          questionnaire_version: questionnaire.version,
          date_collecte: collecteDate,
          periode_collecte: collectePeriod,
          campaign_id: activeCampaign?.id ?? null,
          site_id: siteSelectionMode === 'existing' ? siteNumber : null,
          is_new_site: siteSelectionMode === 'new',
          new_site: normalizedNewSitePayload,
          province_id: selectedProvinceId ? Number(selectedProvinceId) : null,
          territoire_id: selectedTerritoireId ? Number(selectedTerritoireId) : null,
          commune_id: selectedCommuneId ? Number(selectedCommuneId) : null,
          answers: questionnaireAnswers,
          photos,
          statut: 'soumis',
        }
      : {
          ...sectorData,
          sector: selectedSector,
          date_collecte: collecteDate,
          periode_collecte: collectePeriod,
          campaign_id: activeCampaign?.id ?? null,
          site_id: siteSelectionMode === 'existing' ? siteNumber : null,
          is_new_site: siteSelectionMode === 'new',
          new_site: normalizedNewSitePayload,
          province_id: selectedProvinceId ? Number(selectedProvinceId) : null,
          territoire_id: selectedTerritoireId ? Number(selectedTerritoireId) : null,
          commune_id: selectedCommuneId ? Number(selectedCommuneId) : null,
          statut: 'soumis',
          photos,
        };

    const nowIso = new Date().toISOString();
    const record: FormRecord = {
      id: editingFormId ?? `${questionnaire ? 'q' : 'sector'}-${Date.now()}`,
      type: questionnaire ? 'questionnaire' : 'sector',
      campaign_id: activeCampaign?.id ?? null,
      site_id: siteSelectionMode === 'existing' ? siteNumber : 0,
      sector: questionnaire ? undefined : selectedSector,
      payload,
      created_at: editingFormCreatedAt ?? nowIso,
      status: targetStatus,
    };

    saveFormToDb(record);
    refreshQueue();
    setEditingFormId(null);
    setEditingFormCreatedAt(null);
    setOpenedSavedFormStatus(null);
    setSiteSelectionMode('existing');
    setNewSiteData(INITIAL_NEW_SITE_FORM);
    Alert.alert(
      targetStatus === 'draft' ? (editingFormId ? 'Brouillon mis à jour' : 'Brouillon enregistré') : 'Sauvegardé',
      targetStatus === 'draft'
        ? (editingFormId ? 'Le brouillon a été mis à jour.' : 'Le formulaire est enregistré en brouillon.')
        : questionnaire
        ? 'Questionnaire cartographie enregistré localement.'
        : 'Collecte thématique enregistrée localement.',
    );
    setCollecteListMode('none');
    setActiveTab('collecte');
    setTab('sector');
  };

  const saveGeographyForm = (targetStatus: 'draft' | 'pending') => {
    if (!user) {
      Alert.alert('Connexion requise', 'Connectez-vous pour enregistrer une collecte.');
      return;
    }

    const siteNumber = Number(siteId || activeCampaign?.site_id || 0);
    const hasValidExistingSite = !Number.isNaN(siteNumber) && siteNumber > 0;
    const hasValidSite = siteSelectionMode === 'existing' ? hasValidExistingSite : hasValidNewSite;
    if (!hasValidSite) {
      Alert.alert(
        'Site requis',
        siteSelectionMode === 'new'
          ? 'Renseignez au moins le nom du nouveau site.'
          : 'Veuillez saisir un identifiant de site valide.',
      );
      return;
    }
    const collecteDate = String(dateCollecte || '').trim();
    if (!isValidCollecteDate(collecteDate)) {
      Alert.alert('Date requise', 'Indiquez une date valide au format AAAA-MM-JJ.');
      return;
    }

    if (siteSelectionMode === 'new') {
      setGeometryType('polygon');
      setGpsPolygonCategory('contour_site');
      setGpsPolygonBlockName('');
    }

    const effectiveGeometryType: 'point' | 'polygon' = siteSelectionMode === 'new' ? 'polygon' : geometryType;
    const coordinates = effectiveGeometryType === 'polygon' ? polygonPoints : point ? [point] : [];
    if (coordinates.length === 0) {
      Alert.alert('Géographie incomplète', 'Ajoutez au moins un point de collecte.');
      return;
    }
    if (effectiveGeometryType === 'polygon' && coordinates.length < 3) {
      Alert.alert('Contour incomplet', 'Le contour d’un site doit contenir au moins 3 points.');
      return;
    }
    if (effectiveGeometryType === 'point') {
      if (!gpsPointCategory) {
        Alert.alert('Catégorie requise', 'Sélectionnez la catégorie du point GPS.');
        return;
      }
      if (gpsPointCategory === 'autre' && !String(gpsPointOtherLabel || '').trim()) {
        Alert.alert('Précision requise', 'Précisez la catégorie "Autre" du point GPS.');
        return;
      }
    }
    if (effectiveGeometryType === 'polygon' && siteSelectionMode !== 'new') {
      if (!gpsPolygonCategory) {
        Alert.alert('Catégorie requise', 'Sélectionnez la catégorie du polygone GPS.');
        return;
      }
      if (gpsPolygonCategory === 'bloc' && !String(gpsPolygonBlockName || '').trim()) {
        Alert.alert('Nom du bloc requis', 'Saisissez le nom du bloc pour ce polygone.');
        return;
      }
    }

    const normalizedNewSitePayload = siteSelectionMode === 'new'
      ? {
          ...newSiteData,
          nom: String(newSiteData.nom ?? '').trim(),
          code_site: String(newSiteData.code_site ?? '').trim(),
          province: selectedProvince?.name || null,
          territoire: selectedTerritoire?.name || null,
          zone_sante: selectedCommune?.name || null,
          commune_id: selectedCommuneId ? Number(selectedCommuneId) : (newSiteData.commune_id ? Number(newSiteData.commune_id) : null),
          type_gestion: String(newSiteData.type_gestion ?? '').trim() || null,
          source: 'mobile',
        }
      : null;

    const payload = {
      campaign_id: activeCampaign?.id ?? null,
      periode_collecte: activeCampaign?.period_mm_yyyy ?? collecteDateToPeriod(collecteDate),
      date_collecte: collecteDate,
      latitude: coordinates[0].latitude,
      longitude: coordinates[0].longitude,
      site_id: siteSelectionMode === 'existing' ? siteNumber : null,
      is_new_site: siteSelectionMode === 'new',
      new_site: normalizedNewSitePayload,
      province_id: selectedProvinceId ? Number(selectedProvinceId) : null,
      territoire_id: selectedTerritoireId ? Number(selectedTerritoireId) : null,
      commune_id: selectedCommuneId ? Number(selectedCommuneId) : null,
      accuracy_meters: Number(errorMargin) || 0,
      geometry_type: effectiveGeometryType,
      point_category: effectiveGeometryType === 'point' ? gpsPointCategory : null,
      point_category_other: effectiveGeometryType === 'point' && gpsPointCategory === 'autre' ? String(gpsPointOtherLabel).trim() : null,
      polygon_category: effectiveGeometryType === 'polygon' ? (siteSelectionMode === 'new' ? 'contour_site' : gpsPolygonCategory) : null,
      polygon_block_name: effectiveGeometryType === 'polygon' && siteSelectionMode !== 'new' && gpsPolygonCategory === 'bloc' ? String(gpsPolygonBlockName).trim() : null,
      photos,
      geojson: {
        type: 'FeatureCollection',
        features: [
          {
            type: 'Feature',
            properties: {
              source: 'mobile_app',
              geometry_type: effectiveGeometryType,
              accuracy_meters: Number(errorMargin) || 0,
              point_category: effectiveGeometryType === 'point' ? gpsPointCategory : null,
              point_category_other: effectiveGeometryType === 'point' && gpsPointCategory === 'autre' ? String(gpsPointOtherLabel).trim() : null,
              polygon_category: effectiveGeometryType === 'polygon' ? (siteSelectionMode === 'new' ? 'contour_site' : gpsPolygonCategory) : null,
              polygon_block_name: effectiveGeometryType === 'polygon' && siteSelectionMode !== 'new' && gpsPolygonCategory === 'bloc' ? String(gpsPolygonBlockName).trim() : null,
            },
            geometry: effectiveGeometryType === 'polygon'
              ? {
                  type: 'Polygon',
                  coordinates: [coordinates.map((coord) => [coord.longitude, coord.latitude])],
                }
              : {
                  type: 'Point',
                  coordinates: [coordinates[0].longitude, coordinates[0].latitude],
                },
          },
        ],
      },
    };

    const record: FormRecord = {
      id: editingGeographyFormId ?? `geo-${Date.now()}`,
      type: 'geography',
      campaign_id: activeCampaign?.id ?? null,
      site_id: siteSelectionMode === 'existing' ? siteNumber : 0,
      payload,
      created_at: editingGeographyCreatedAt ?? new Date().toISOString(),
      status: targetStatus,
    };

    saveFormToDb(record);
    refreshQueue();
    stopPolygonTracking();
    setEditingGeographyFormId(null);
    setEditingGeographyCreatedAt(null);
    setSiteSelectionMode('existing');
    setNewSiteData(INITIAL_NEW_SITE_FORM);
    setGeographyPanelMode('list');
    Alert.alert(
      targetStatus === 'draft' ? 'Brouillon enregistré' : 'Prêt à envoyer',
      targetStatus === 'draft'
        ? 'La collecte géographique est enregistrée dans les brouillons.'
        : 'La collecte géographique est enregistrée et prête à être envoyée.',
    );
  };

  const saveOssatDraft = async () => {
    if (!user) {
      Alert.alert('Connexion requise', 'Connectez-vous pour enregistrer une collecte.');
      return;
    }

    const siteNumber = Number(activeCampaign?.site_id ?? siteId);
    if (!siteNumber || Number.isNaN(siteNumber)) {
      Alert.alert('Site requis', 'Veuillez saisir un identifiant de site valide.');
      return;
    }
    if (!activeCampaign) {
      Alert.alert('Campagne requise', 'Créez ou sélectionnez une campagne avant de saisir la collecte.');
      return;
    }

    const payload = {
      ...ossatData,
      campaign_id: activeCampaign.id,
      periode_collecte: activeCampaign.period_mm_yyyy,
      site_id: siteNumber,
      asset_id: `ossat-${Date.now()}`,
      date_collecte: periodToCollecteDate(activeCampaign.period_mm_yyyy),
      collecteur_nom: user.name,
      collecteur_email: user.email,
      province_id: selectedProvinceId ? Number(selectedProvinceId) : null,
      territoire_id: selectedTerritoireId ? Number(selectedTerritoireId) : null,
      commune_id: selectedCommuneId ? Number(selectedCommuneId) : null,
      latitude: point?.latitude ?? null,
      longitude: point?.longitude ?? null,
      geographic_accuracy_m: Number(errorMargin) || 0,
      photos,
      statut: 'soumis',
      source: 'mobile_app',
    };

    const record: FormRecord = {
      id: `ossat-${Date.now()}`,
      type: 'ossat',
      campaign_id: activeCampaign.id,
      site_id: siteNumber,
      payload,
      created_at: new Date().toISOString(),
      status: 'pending',
    };

    saveFormToDb(record);
    refreshQueue();
    Alert.alert('Sauvegardé', 'Formulaire OSSAT enregistré dans la file locale.');
  };

  const uploadOnePhoto = async (uri: string): Promise<string> => {
    if (!uri || uri.startsWith('http')) {
      return uri;
    }

    const name = uri.split('/').pop() || `photo-${Date.now()}.jpg`;
    const formData = new FormData();
    formData.append('photo', {
      uri,
      name,
      type: 'image/jpeg',
    } as any);

    const response = await fetch(`${apiBase}/api/mobile/photo-upload`, {
      method: 'POST',
      body: formData,
      headers: { Accept: 'application/json' },
    });

    const payload = await response.json();
    if (!response.ok || !payload?.success) {
      throw new Error(payload?.message || 'Téléversement photo impossible.');
    }

    return payload.url;
  };

  const syncQueue = async (campaignId?: number | null, selectedIds?: string[]): Promise<boolean> => {
    if (!user) {
      Alert.alert('Connexion requise', 'Connectez-vous avant la synchronisation.');
      return false;
    }

    const selectedIdSet = selectedIds && selectedIds.length > 0 ? new Set(selectedIds) : null;
    const selectedPending = readReconciledStoredForms().filter((item) => {
      if (item.status !== 'pending') {
        return false;
      }
      if (selectedIdSet && !selectedIdSet.has(item.id)) {
        return false;
      }
      if (!campaignId) {
        return true;
      }
      return Number(item.campaign_id ?? 0) === Number(campaignId);
    });
    if (selectedPending.length === 0) {
      Alert.alert(
        'Aucune donnée',
        selectedIdSet
          ? 'Aucun formulaire sélectionné à synchroniser.'
          : campaignId
          ? 'Aucune donnée en attente pour cette campagne.'
          : 'La file locale est vide.',
      );
      return false;
    }
    const invalidItems = selectedPending.filter((item) => !checkSyncConformity(item).valid);
    if (invalidItems.length > 0) {
      const firstError = checkSyncConformity(invalidItems[0]).errors[0];
      Alert.alert(
        'Données non conformes',
        `${invalidItems.length} élément(s) ne peuvent pas être envoyés. ${firstError ?? ''}`.trim(),
      );
      return false;
    }
    const pending = selectedPending.map(normalizeRecordForSync);

    setIsSyncing(true);
    try {
      const syncedMessages: string[] = [];
      const failedMessages: string[] = [];
      let movementsToCorrect = 0;

      for (const item of pending) {
        let payloadForSync = item.payload;
        try {
          const photosToSync = Array.isArray(item.payload?.photos) ? item.payload.photos : [];
          const uploadedPhotos = await Promise.all(photosToSync.map((photoUri) => uploadOnePhoto(String(photoUri))));
          payloadForSync = { ...item.payload, photos: uploadedPhotos };

          if (item.type === 'ossat') {
            const response = await fetch(`${apiBase}/api/mobile/ossat/save`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
              body: JSON.stringify({ user_id: user.id, site_id: item.site_id, payload: { ...payloadForSync, photos: payloadForSync.photos || [] } }),
            });

            const result = await response.json();
            if (!response.ok || !result?.success) {
              throw new Error(result?.message || 'Erreur de synchronisation OSSAT.');
            }

            syncedMessages.push(`OSSAT ${item.id}: ${result.message || 'OK'}`);
          } else if (item.type === 'questionnaire') {
            const response = await fetch(`${apiBase}/api/mobile/questionnaire/submit`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
              body: JSON.stringify({
                user_id: user.id,
                questionnaire_code: payloadForSync?.questionnaire_code || 'service-cartography',
                date_collecte: payloadForSync?.date_collecte,
                site_id: payloadForSync?.is_new_site ? null : item.site_id,
                is_new_site: Boolean(payloadForSync?.is_new_site),
                new_site: payloadForSync?.new_site ?? null,
                province_id: payloadForSync?.province_id ?? null,
                territoire_id: payloadForSync?.territoire_id ?? null,
                commune_id: payloadForSync?.commune_id ?? null,
                answers: payloadForSync?.answers || {},
              }),
            });

            const result = await response.json();
            if (!response.ok || !result?.success) {
              throw new Error(result?.message || 'Erreur de synchronisation du questionnaire.');
            }

            syncedMessages.push(`Questionnaire ${item.id}: ${result.message || 'OK'}`);
          } else {
            const response = await fetch(`${apiBase}/api/mobile/sync`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
              body: JSON.stringify({ user_id: user.id, records: [{ ...item, payload: { ...payloadForSync, photos: payloadForSync.photos || [] } }] }),
            });

            const result = await response.json();
            if (!response.ok || !result?.success || Number(result?.processed ?? 0) < 1) {
              const syncFailure = result?.errors?.[0];
              const errorMessage = syncFailure?.message || result?.message || 'Erreur de synchronisation.';
              throw new SyncRequestError(
                errorMessage,
                parseSyncErrorDetails(syncFailure?.field_errors, errorMessage),
              );
            }
            syncedMessages.push(`Collecte ${item.id}: ${result.message || 'OK'}`);
          }

          saveFormToDb({
            ...item,
            payload: payloadForSync,
            status: 'synced',
            sync_error: null,
            sync_error_details: [],
          });
        } catch (error) {
          const errorMessage = error instanceof Error ? error.message : 'Échec de la synchronisation.';
          const errorDetails = error instanceof SyncRequestError
            ? error.details
            : parseSyncErrorDetails(null, errorMessage);
          failedMessages.push(`${item.type === 'movement' ? 'Mouvement' : 'Formulaire'} ${item.id}: ${errorMessage}`);

          if (item.type === 'movement') {
            saveFormToDb({
              ...item,
              payload: payloadForSync,
              status: 'correction',
              sync_error: errorMessage,
              sync_error_details: errorDetails,
            });
            movementsToCorrect++;
          }
        }
      }

      refreshQueue();
      refreshCampaigns();
      setPhotos([]);

      if (failedMessages.length > 0) {
        const correctionMessage = movementsToCorrect > 0
          ? `${movementsToCorrect} mouvement(s) placé(s) dans « À corriger ».\n`
          : '';
        const wasRejected = failedMessages.some((message) => normalizeLookupValue(message).includes('refus'));
        Alert.alert(
          syncedMessages.length > 0
            ? 'Synchronisation partielle'
            : wasRejected
            ? 'Synchronisation refusée'
            : 'Synchronisation échouée',
          `${syncedMessages.length} élément(s) synchronisé(s).\n${correctionMessage}${failedMessages.slice(0, 3).join('\n')}\nServeur: ${apiBase}`,
        );
        return false;
      }

      Alert.alert(
        campaignId ? 'Synchronisation campagne' : 'Synchronisation',
        syncedMessages.join('\n') || 'Synchronisation terminée.',
      );
      return true;
    } finally {
      setIsSyncing(false);
    }
  };

  const actionCatalog = [
    { key: 'Thématique', icon: '📝', tone: 'light' },
    { key: 'Géographie', icon: '📍', tone: 'light' },
    { key: 'Mouvements', icon: '👥', tone: 'light' },
    { key: 'OSSAT', icon: '🧭', tone: 'light' },
    { key: 'Photos', icon: '📷', tone: 'light' },
    { key: 'Synchroniser', icon: '⇄', tone: 'highlight' },
    { key: 'File locale', icon: '🗂️', tone: 'light' },
  ] as const;
  const collecteActionCatalog = [
    { key: 'Nouvelle campagne', icon: '🗓️', tone: 'highlight' },
    { key: 'Nouvelle collecte', icon: '🆕', tone: 'light' },
    { key: 'Brouillons', icon: '🗂️', tone: 'light' },
    { key: 'Pret a envoyer', icon: '📤', tone: 'highlight' },
    { key: 'Sync', icon: '✅', tone: 'light' },
  ] as const;

  const renderSearchablePicklist = (
    pickerKey: string,
    label: string,
    options: PicklistOption[],
    selectedLabel: string,
    onSelect: (value: string) => void,
    placeholder: string,
    emptyMessage: string,
    multiple = false,
    selectedValues: string[] = [],
    singleSelectedValue = '',
    disabled = false,
  ) => {
    const searchText = pickerSearchMap[pickerKey] ?? '';
    const normalizedSearch = searchText.trim().toLowerCase();
    const filteredOptions = normalizedSearch === ''
      ? options
      : options.filter((option) => option.label.toLowerCase().includes(normalizedSearch));
    const isOpen = activePickerKey === pickerKey;
    const selectedCount = multiple ? selectedValues.length : (singleSelectedValue ? 1 : 0);
    const selectedBadges = multiple
      ? options.filter((option) => selectedValues.includes(option.value)).map((option) => option.label)
      : (selectedLabel ? [selectedLabel] : []);
    const visibleBadges = selectedBadges.slice(0, 2);

    return (
      <View style={styles.formFieldWrapper}>
        <Text style={styles.fieldLabel}>{label}</Text>
        <TouchableOpacity
          style={[styles.picklistTrigger, disabled && styles.picklistTriggerDisabled]}
          onPress={() => {
            if (disabled) {
              return;
            }
            if (isOpen) {
              setActivePickerKey(null);
              setPickerSearchMap((prev) => ({ ...prev, [pickerKey]: '' }));
              return;
            }
            setActivePickerKey(pickerKey);
          }}
        >
          <Text style={selectedLabel ? styles.picklistTriggerText : styles.picklistPlaceholderText}>
            {selectedLabel || placeholder}
          </Text>
          <Text style={styles.picklistChevron}>{isOpen ? '▲' : '▼'}</Text>
        </TouchableOpacity>

        {selectedCount > 0 ? (
          <View style={styles.picklistBadgeRow}>
            {visibleBadges.map((badgeLabel, badgeIndex) => (
              <View key={`${pickerKey}-badge-${badgeLabel}-${badgeIndex}`} style={styles.picklistBadge}>
                <Text style={styles.picklistBadgeText} numberOfLines={1}>{badgeLabel}</Text>
              </View>
            ))}
            {selectedBadges.length > visibleBadges.length ? (
              <View style={styles.picklistBadgeCounter}>
                <Text style={styles.picklistBadgeCounterText}>+{selectedBadges.length - visibleBadges.length}</Text>
              </View>
            ) : null}
          </View>
        ) : null}

        {isOpen && !disabled ? (
          <View style={styles.picklistPanel}>
            <View style={styles.picklistPanelHeader}>
              <Text style={styles.picklistPanelHint}>
                {filteredOptions.length} option{filteredOptions.length > 1 ? 's' : ''}
              </Text>
              <TouchableOpacity
                style={styles.picklistCloseButton}
                onPress={() => {
                  setActivePickerKey(null);
                  setPickerSearchMap((prev) => ({ ...prev, [pickerKey]: '' }));
                }}
              >
                <Text style={styles.picklistCloseButtonText}>Fermer</Text>
              </TouchableOpacity>
            </View>
            <TextInput
              style={styles.picklistSearchInput}
              value={searchText}
              onChangeText={(value) => setPickerSearchMap((prev) => ({ ...prev, [pickerKey]: value }))}
              placeholder="Rechercher..."
              placeholderTextColor="#94a3b8"
            />

            {filteredOptions.length === 0 ? (
              <Text style={styles.secondaryActionText}>{emptyMessage}</Text>
            ) : (
              <ScrollView style={[styles.picklistOptionsContainer, { maxHeight: picklistMaxHeight }]} nestedScrollEnabled>
                {filteredOptions.map((option) => {
                  const isSelected = multiple ? selectedValues.includes(option.value) : option.value === singleSelectedValue;
                  return (
                    <TouchableOpacity
                      key={`${pickerKey}-${option.value}`}
                      style={[styles.picklistOption, isSelected && styles.picklistOptionActive]}
                      onPress={() => {
                        if (multiple) {
                          const nextValues = selectedValues.includes(option.value)
                            ? selectedValues.filter((value) => value !== option.value)
                            : [...selectedValues, option.value];
                          onSelect(nextValues.join(' ').trim());
                        } else {
                          onSelect(option.value);
                          setActivePickerKey(null);
                        }
                      }}
                    >
                      <Text style={[styles.picklistOptionText, isSelected && styles.picklistOptionTextActive]}>
                        {option.label}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </ScrollView>
            )}
          </View>
        ) : null}
      </View>
    );
  };

  const renderCascadeField = (
    pickerKey: string,
    label: string,
    options: PicklistOption[],
    selectedValue: string,
    onSelect: (value: string) => void,
    emptyMessage: string,
    placeholder: string,
    disabled = false,
  ) => renderSearchablePicklist(
    pickerKey,
    label,
    options,
    options.find((option) => option.value === selectedValue)?.label ?? '',
    onSelect,
    placeholder,
    emptyMessage,
    false,
    [],
    selectedValue,
    disabled,
  );

  const renderSiteCascade = (disabled = false, allowNewSite = true) => (
    <View style={styles.formFieldWrapper}>
      {allowNewSite ? (
        <>
          <Text style={styles.fieldLabel}>Type de site</Text>
          <View style={styles.toggleRow}>
            <TouchableOpacity
              style={[styles.toggleButton, siteSelectionMode === 'existing' && styles.toggleButtonActive]}
              onPress={() => {
                if (disabled) {
                  return;
                }
                setSiteSelectionMode('existing');
              }}
            >
              <Text style={[styles.toggleButtonText, siteSelectionMode === 'existing' && styles.toggleButtonTextActive]}>Site existant</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.toggleButton, siteSelectionMode === 'new' && styles.toggleButtonActive]}
              onPress={() => {
                if (disabled) {
                  return;
                }
                setSiteSelectionMode('new');
                setGeometryType('polygon');
                setGpsPointCategory('');
                setGpsPointOtherLabel('');
                setGpsPolygonCategory('contour_site');
                setGpsPolygonBlockName('');
              }}
            >
              <Text style={[styles.toggleButtonText, siteSelectionMode === 'new' && styles.toggleButtonTextActive]}>Nouveau site</Text>
            </TouchableOpacity>
          </View>
        </>
      ) : null}

      {referenceProvinces.length > 0 || referenceSites.length > 0 ? (
        <>
          {renderCascadeField(
            'province',
            'Province',
            referenceProvinces.map((item) => ({ value: String(item.id), label: item.name })),
            selectedProvinceId,
            (value) => {
              setSelectedProvinceId(value);
              setSelectedTerritoireId('');
              setSelectedCommuneId('');
              setSiteId('');
            },
            'Aucune province disponible.',
            'Choisir une province',
            disabled,
          )}

          {renderCascadeField(
            'territoire',
            'Territoire',
            filteredTerritoires.map((item) => ({ value: String(item.id), label: item.name })),
            selectedTerritoireId,
            (value) => {
              setSelectedTerritoireId(value);
              setSelectedCommuneId('');
              setSiteId('');
            },
            selectedProvinceId ? 'Aucun territoire pour cette province.' : 'Sélectionnez d’abord une province.',
            'Choisir un territoire',
            disabled,
          )}

          {renderCascadeField(
            'commune',
            'Commune',
            filteredCommunes.map((item) => ({ value: String(item.id), label: item.name })),
            selectedCommuneId,
            (value) => {
              setSelectedCommuneId(value);
              setSiteId('');
              if (siteSelectionMode === 'new') {
                updateNewSiteField('commune_id', value);
              }
            },
            selectedTerritoireId ? 'Aucune commune pour ce territoire.' : 'Sélectionnez d’abord un territoire.',
            'Choisir une commune',
            disabled,
          )}
        </>
      ) : null}

      {siteSelectionMode === 'existing' || !allowNewSite ? (
        <>
          {referenceProvinces.length === 0 && referenceSites.length === 0 ? (
            <>
              <Text style={styles.fieldLabel}>Site</Text>
              <TextInput
                style={[styles.input, disabled && styles.inputDisabled]}
                value={siteId}
                onChangeText={setSiteId}
                editable={!disabled}
                keyboardType="numeric"
                placeholder="ID du site"
                placeholderTextColor="#94a3b8"
              />
            </>
          ) : null}

          {referenceProvinces.length > 0 || referenceSites.length > 0 ? (
            renderCascadeField(
              'site',
              'Site',
              filteredSites.map((site) => ({
                value: String(site.id),
                label: `${String(site.nom ?? `Site ${site.id}`)}${site.code_site ? ` (${site.code_site})` : ''}`,
              })),
              siteId,
              (value) => setSiteId(value),
              selectedCommuneId || selectedTerritoireId || selectedProvinceId
                ? 'Aucun site trouvé pour la sélection.'
                : 'Sélectionnez d’abord Province, Territoire et Commune.',
              'Choisir un site',
              disabled,
            )
          ) : null}
        </>
      ) : (
        <View style={styles.campaignDetailsCard}>
          <Text style={styles.campaignDetailsTitle}>Nouveau site (table sites)</Text>
          <Text style={styles.campaignDetailsHint}>Renseignez les informations essentielles du nouveau site.</Text>
          <Text style={styles.fieldLabel}>Nom du site *</Text>
          <TextInput
            style={[styles.input, disabled && styles.inputDisabled]}
            value={newSiteData.nom}
            onChangeText={(value) => updateNewSiteField('nom', value)}
            editable={!disabled}
            placeholder="Nom du site"
            placeholderTextColor="#94a3b8"
          />
          <Text style={styles.fieldLabel}>Code site</Text>
          <TextInput
            style={[styles.input, disabled && styles.inputDisabled]}
            value={newSiteData.code_site}
            onChangeText={(value) => updateNewSiteField('code_site', value)}
            editable={!disabled}
            placeholder="Code site"
            placeholderTextColor="#94a3b8"
          />
          <Text style={styles.fieldLabel}>Localisation sélectionnée</Text>
          <Text style={styles.campaignCardHint}>
            Province: {selectedProvince?.name ?? '-'} | Territoire: {selectedTerritoire?.name ?? '-'} | Commune: {selectedCommune?.name ?? '-'}
          </Text>
          {renderSearchablePicklist(
            'new-site-type-gestion',
            'Type de gestion',
            TYPE_GESTION_OPTIONS,
            TYPE_GESTION_OPTIONS.find((option) => option.value === String(newSiteData.type_gestion ?? ''))?.label ?? '',
            (value) => updateNewSiteField('type_gestion', value),
            'Choisir un type de gestion',
            'Aucun type trouvé.',
            false,
            [],
            String(newSiteData.type_gestion ?? ''),
            disabled,
          )}
          <Text style={styles.campaignCardHint}>
            Pour un nouveau site en collecte géographique, seul le contour (polygone) est autorisé.
          </Text>
        </View>
      )}
    </View>
  );

  const renderDashboardScreen = () => (
    <ScrollView contentContainerStyle={[styles.screenContent, { paddingBottom: 110 }]}>
      <View style={styles.topStatusBar}>
        <View style={styles.brandRow}>
          <Image source={{ uri: `${apiBase}/images/logo-dms-cccm.avif` }} style={styles.brandLogoMini} resizeMode="contain" />
          <Text style={styles.appBrand}>DMS CCCM</Text>
        </View>
        <View style={styles.statusRight}>
          <Text style={styles.statusText}>2.04</Text>
          <Text style={styles.statusText}>KB/s</Text>
          <View style={styles.signalGroup}>
            <View style={styles.signalBarShort} />
            <View style={styles.signalBarMedium} />
            <View style={styles.signalBarLong} />
          </View>
          <View style={styles.avatarShell}>
            <Text style={styles.avatarText}>◔</Text>
          </View>
        </View>
      </View>

      <Text style={styles.familyName}>{user?.name ?? 'Agent'}</Text>

      <Text style={styles.screenTitle}>Que voulez-vous faire ?</Text>

      <View style={styles.primaryCardsRow}>
        <TouchableOpacity style={styles.primaryCardDark} onPress={() => { setActiveTab('collecte'); setTab('sector'); }}>
          <View style={styles.cardIconShell}>
            <Text style={styles.cardIcon}>📝</Text>
          </View>
          <Text style={styles.bigCardTitle}>{'Carto\nService'}</Text>
          <Text style={styles.cardHint}>Tableau de bord collecte</Text>
          <View style={styles.arrowBadge}><Text style={styles.arrowText}>→</Text></View>
        </TouchableOpacity>

        <TouchableOpacity style={styles.primaryCardDark} onPress={() => { setActiveTab('geography'); setGeographyPanelMode('list'); setTab('geography'); }}>
          <View style={styles.cardIconShell}>
            <Text style={styles.cardIcon}>📍</Text>
          </View>
          <Text style={styles.bigCardTitle}>Géographie</Text>
          <Text style={styles.cardHint}>Carte et points du site</Text>
          <View style={styles.arrowBadge}><Text style={styles.arrowText}>→</Text></View>
        </TouchableOpacity>
      </View>

      <Text style={styles.sectionTitle}>Autres actions</Text>

      <View style={styles.actionGrid}>
        {actionCatalog.map((action) => (
          <TouchableOpacity
            key={action.key}
            style={[styles.actionCard, action.tone === 'highlight' && styles.actionCardHighlight]}
            onPress={() => openAction(action.key)}
          >
            <Text style={styles.actionIcon}>{action.icon}</Text>
            <Text style={styles.actionLabel}>{action.key}</Text>
          </TouchableOpacity>
        ))}
      </View>
    </ScrollView>
  );

  const renderSectorForm = () => (
    <ScrollView contentContainerStyle={[styles.screenContent, { paddingBottom: 170 }]}>
      <View style={styles.topStatusBar}>
        <View style={styles.brandRow}>
          <TouchableOpacity onPress={() => { setActiveTab('dashboard'); setTab('dashboard'); }} style={styles.backButton}>
            <Text style={styles.backButtonText}>←</Text>
          </TouchableOpacity>
          <Image source={{ uri: `${apiBase}/images/logo-dms-cccm.avif` }} style={styles.brandLogoMini} resizeMode="contain" />
          <Text style={styles.appBrand}>Collecte</Text>
        </View>
      </View>

      <Text style={styles.formTitle}>Carto Service</Text>
      <View style={styles.collecteIntroCard}>
        <Text style={styles.collecteIntroTitle}>Saisie terrain</Text>
        <Text style={styles.collecteIntroText}>
          Vérifiez le site, choisissez la section et complétez les champs obligatoires avant d'enregistrer.
        </Text>
      </View>

      <Text style={styles.collecteSectionTitle}>Tableau de bord</Text>
      <Text style={styles.collecteSectionHint}>Vue rapide de la progression de vos collectes.</Text>
      <View style={[styles.quickActionsCard, styles.collecteDashboardCard]}>
        <View style={styles.campaignSummaryRow}>
          <View style={[styles.campaignSummaryChip, styles.collecteSummaryChip]}>
            <Text style={styles.campaignSummaryValue}>{campaignSummary.synchronise}</Text>
            <Text style={styles.campaignSummaryLabel}>Synchronisé</Text>
          </View>
          <View style={[styles.campaignSummaryChip, styles.collecteSummaryChip]}>
            <Text style={styles.campaignSummaryValue}>{campaignSummary.en_attente}</Text>
            <Text style={styles.campaignSummaryLabel}>En attente</Text>
          </View>
          <View style={[styles.campaignSummaryChip, styles.collecteSummaryChip]}>
            <Text style={styles.campaignSummaryValue}>{campaignSummary.brouillon}</Text>
            <Text style={styles.campaignSummaryLabel}>Brouillon</Text>
          </View>
        </View>
      </View>

      <Text style={styles.collecteSectionTitle}>Autres actions</Text>
      <View style={styles.collecteActionGrid}>
        {collecteActionCatalog.map((action) => (
          <TouchableOpacity
            key={`collecte-${action.key}`}
            style={[styles.actionCard, styles.collecteActionCard, action.tone === 'highlight' && styles.actionCardHighlight]}
            onPress={() => openCollecteAction(action.key)}
          >
            <Text style={styles.actionIcon}>{action.icon}</Text>
            <Text style={styles.collecteActionLabel}>
              {action.key === 'Brouillons'
                ? `${action.key} (${draftFormCount})`
                : action.key === 'Pret a envoyer'
                ? `${action.key} (${pendingFormCount})`
                : action.key === 'Sync'
                ? `${action.key} (${syncedFormCount})`
                : action.key}
            </Text>
          </TouchableOpacity>
        ))}
      </View>
    </ScrollView>
  );

  const renderCampaignForm = () => (
    <ScrollView contentContainerStyle={[styles.screenContent, { paddingBottom: 130 }]}>
      <View style={styles.topStatusBar}>
        <View style={styles.brandRow}>
          <TouchableOpacity onPress={() => { setActiveTab('collecte'); setTab('sector'); }} style={styles.backButton}>
            <Text style={styles.backButtonText}>←</Text>
          </TouchableOpacity>
          <Image source={{ uri: `${apiBase}/images/logo-dms-cccm.avif` }} style={styles.brandLogoMini} resizeMode="contain" />
          <Text style={styles.appBrand}>Campagne</Text>
        </View>
      </View>

      <Text style={styles.formTitle}>{campaignPanelMode === 'standard' ? 'Nouvelle collecte' : 'Nouvelle campagne'}</Text>
      <View style={styles.formCardBlock}>
        {campaignPanelMode === 'menu' ? (
          <View style={styles.campaignCard}>
            <Text style={styles.campaignCardTitle}>Campagnes locales</Text>
            <Text style={styles.campaignCardHint}>Sélectionnez une campagne existante ou ajoutez une nouvelle campagne.</Text>
            {campaignOverviews.length === 0 ? (
              <View style={styles.emptyQuestionCard}>
                <Text style={styles.emptyQuestionText}>Aucune campagne active enregistrée en local.</Text>
              </View>
            ) : (
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.campaignListRow}>
                {campaignOverviews.map((campaign) => {
                  const isActiveCampaign = activeCampaignId === campaign.id;
                  const statusLabel = campaign.status === 'synchronise'
                    ? 'Synchronisé'
                    : campaign.status === 'en_attente'
                    ? 'En attente'
                    : 'Brouillon';

                  return (
                    <TouchableOpacity
                      key={campaign.id}
                      style={[styles.campaignItemCard, isActiveCampaign && styles.campaignItemCardActive]}
                      onPress={() => setActiveCampaignId(campaign.id)}
                    >
                      <Text style={[styles.campaignItemPeriod, isActiveCampaign && styles.campaignItemPeriodActive]}>{campaign.period_mm_yyyy}</Text>
                      <Text style={[styles.campaignItemSite, isActiveCampaign && styles.campaignItemSiteActive]} numberOfLines={2}>{campaign.site_label}</Text>
                      <Text style={[styles.campaignItemStatus, isActiveCampaign && styles.campaignItemStatusActive]}>{statusLabel}</Text>
                      <Text style={[styles.campaignItemMeta, isActiveCampaign && styles.campaignItemMetaActive]}>
                        {campaign.synced_count} sync · {campaign.pending_count} attente
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </ScrollView>
            )}
            <View style={styles.inlineActions}>
              <TouchableOpacity
                style={styles.secondaryActionButton}
                onPress={() => {
                  setSelectedProvinceId('');
                  setSelectedTerritoireId('');
                  setSelectedCommuneId('');
                  setSiteId('');
                  setCampaignPeriodInput(`${String(new Date().getMonth() + 1).padStart(2, '0')}-${new Date().getFullYear()}`);
                  setCampaignPanelMode('create');
                }}
              >
                <Text style={styles.secondaryActionText}>Ajouter une nouvelle campagne</Text>
              </TouchableOpacity>
            </View>
          </View>
        ) : campaignPanelMode === 'create' ? (
          <View style={styles.campaignCard}>
            <Text style={styles.campaignCardTitle}>Formulaire campagne</Text>
            <Text style={styles.campaignCardHint}>Saisissez le site et la période de la nouvelle campagne.</Text>
            {renderSiteCascade()}
            <Text style={styles.fieldLabel}>Période de collecte (MM-AAAA)</Text>
            <TextInput
              style={styles.input}
              value={campaignPeriodInput}
              onChangeText={setCampaignPeriodInput}
              placeholder="08-2026"
              placeholderTextColor="#94a3b8"
            />
            <View style={styles.campaignDetailsCard}>
              <Text style={styles.campaignDetailsTitle}>Rubriques du formulaire service</Text>
              <Text style={styles.campaignDetailsHint}>
                Cliquez sur une rubrique pour créer la campagne et ouvrir directement la collecte sur ce service.
              </Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.campaignFilterRow}>
                {collecteMenuSections.map((section) => (
                  <TouchableOpacity
                    key={`campaign-create-section-${section.key}`}
                    style={styles.campaignFilterChip}
                    onPress={() => handleCreateCampaign(true, section.key)}
                  >
                    <Text style={styles.campaignFilterChipText}>{section.label}</Text>
                  </TouchableOpacity>
                ))}
              </ScrollView>
            </View>
            <View style={styles.inlineActions}>
              <TouchableOpacity style={styles.secondaryActionButton} onPress={() => handleCreateCampaign(false)}>
                <Text style={styles.secondaryActionText}>Créer une campagne</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.primaryButton} onPress={() => handleCreateCampaign(true)}>
                <Text style={styles.primaryButtonText}>Créer et collecter maintenant</Text>
              </TouchableOpacity>
            </View>
            {activeCampaign ? (
              <View style={styles.inlineActions}>
                <TouchableOpacity
                  style={styles.secondaryActionButton}
                  onPress={() => {
                    setActiveTab('collecte');
                    setTab('collecte_form');
                    setOpenedSavedFormStatus(null);
                    setEditingFormId(null);
                    setEditingFormCreatedAt(null);
                  }}
                >
                  <Text style={styles.secondaryActionText}>Collecter sur cette campagne</Text>
                </TouchableOpacity>
              </View>
            ) : null}
            <View style={styles.inlineActions}>
              <TouchableOpacity style={styles.archiveActionButton} onPress={() => setCampaignPanelMode('menu')}>
                <Text style={styles.archiveActionText}>Retour aux campagnes</Text>
              </TouchableOpacity>
            </View>
          </View>
        ) : (
          <>
            <View style={styles.campaignCard}>
              <Text style={styles.campaignCardTitle}>Nouvelle collecte carto service</Text>
              <Text style={styles.campaignCardHint}>Ouvrez directement le formulaire de collecte des services.</Text>
              <TouchableOpacity
                style={styles.primaryButton}
                onPress={() => {
                  setActiveTab('collecte');
                  setTab('collecte_form');
                  setOpenedSavedFormStatus(null);
                  setEditingFormId(null);
                  setEditingFormCreatedAt(null);
                }}
              >
                <Text style={styles.primaryButtonText}>Ajouter collecte carto service</Text>
              </TouchableOpacity>
            </View>
            {collecteListMode !== 'none' ? (
              <View style={styles.campaignDetailsCard}>
                <Text style={styles.campaignDetailsTitle}>
                  {collecteListMode === 'draft'
                    ? 'Brouillons formulaires'
                    : collecteListMode === 'pending'
                    ? 'Formulaires prêt à envoyer'
                    : 'Formulaires synchronisés'}
                </Text>
                <Text style={styles.campaignDetailsHint}>
                  {collecteListMode === 'draft'
                    ? 'Liste des formulaires service enregistrés en brouillon.'
                    : collecteListMode === 'pending'
                    ? 'Liste des formulaires service enregistrés et en attente de synchronisation.'
                    : 'Liste des formulaires service déjà synchronisés.'}
                </Text>
                <View style={styles.campaignTimelineList}>
                  {filteredServiceCollecteForms.length === 0 ? (
                    <View style={styles.emptyQuestionCard}>
                      <Text style={styles.emptyQuestionText}>
                        {collecteListMode === 'draft'
                          ? 'Aucun brouillon enregistré.'
                          : collecteListMode === 'pending'
                          ? 'Aucun formulaire en attente pour envoi.'
                          : 'Aucun formulaire synchronisé.'}
                      </Text>
                    </View>
                  ) : (
                    filteredServiceCollecteForms.map((item) => (
                      (() => {
                        const provinceId = Number(item.payload?.province_id ?? 0);
                        const territoireId = Number(item.payload?.territoire_id ?? 0);
                        const communeId = Number(item.payload?.commune_id ?? 0);
                        const resolvedSiteId = Number(item.payload?.site_id ?? item.site_id ?? 0);
                        const provinceName = referenceProvinces.find((entry) => Number(entry.id) === provinceId)?.name ?? '-';
                        const territoireName = referenceTerritoires.find((entry) => Number(entry.id) === territoireId)?.name ?? '-';
                        const communeName = referenceCommunes.find((entry) => Number(entry.id) === communeId)?.name ?? '-';
                        const site = referenceSites.find((entry) => Number(entry.id) === resolvedSiteId);
                        const siteLabel = site
                          ? `${String(site.nom ?? `Site ${resolvedSiteId}`)}${site.code_site ? ` (${site.code_site})` : ''}`
                          : resolvedSiteId > 0
                          ? `Site ${resolvedSiteId}`
                          : '-';

                        return (
                          <View key={item.id} style={styles.campaignTimelineItem}>
                            <View style={styles.campaignTimelineHeader}>
                              <Text style={styles.campaignTimelineType}>
                                {item.type === 'questionnaire' ? 'Questionnaire' : 'Thématique'}
                              </Text>
                              <View style={styles.pendingStatusWrap}>
                                {collecteListMode === 'pending' ? (
                                  <TouchableOpacity
                                    style={[
                                      styles.pendingSelectBox,
                                      selectedPendingFormIds[item.id] && styles.pendingSelectBoxActive,
                                    ]}
                                    onPress={() =>
                                      setSelectedPendingFormIds((prev) => ({
                                        ...prev,
                                        [item.id]: !prev[item.id],
                                      }))
                                    }
                                  >
                                    <Text style={styles.pendingSelectBoxText}>{selectedPendingFormIds[item.id] ? '✓' : ''}</Text>
                                  </TouchableOpacity>
                                ) : null}
                                <Text
                                  style={[
                                    styles.campaignTimelineStatus,
                                    item.status === 'synced' && styles.campaignTimelineStatusSynced,
                                  ]}
                                >
                                  {item.status === 'draft' ? 'Brouillon' : item.status === 'pending' ? 'En attente' : 'Synchronisé'}
                                </Text>
                              </View>
                            </View>
                            <Text style={styles.campaignTimelineId}>{item.id}</Text>
                            <Text style={styles.campaignTimelineMetaText}>Province: {provinceName}</Text>
                            <Text style={styles.campaignTimelineMetaText}>Territoire: {territoireName}</Text>
                            <Text style={styles.campaignTimelineMetaText}>Commune: {communeName}</Text>
                            <Text style={styles.campaignTimelineMetaText}>Site: {siteLabel}</Text>
                            <Text style={styles.campaignTimelineDate}>
                              {String(item.created_at || '').replace('T', ' ').slice(0, 16)}
                            </Text>
                            <View style={styles.inlineActions}>
                              <TouchableOpacity style={styles.secondaryActionButton} onPress={() => openSavedForm(item)}>
                                <Text style={styles.secondaryActionText}>Voir formulaire</Text>
                              </TouchableOpacity>
                            </View>
                          </View>
                        );
                      })()
                    ))
                  )}
                </View>
                {collecteListMode === 'pending' ? (
                  <View style={styles.inlineActions}>
                    <TouchableOpacity
                      style={styles.secondaryActionButton}
                      onPress={() => {
                        const pendingIds = filteredServiceCollecteForms
                          .filter((item) => item.status === 'pending')
                          .map((item) => item.id);
                        const allSelected = pendingIds.length > 0 && pendingIds.every((id) => selectedPendingFormIds[id]);
                        if (allSelected) {
                          setSelectedPendingFormIds({});
                        } else {
                          setSelectedPendingFormIds(
                            pendingIds.reduce<Record<string, boolean>>((acc, id) => {
                              acc[id] = true;
                              return acc;
                            }, {}),
                          );
                        }
                      }}
                    >
                      <Text style={styles.secondaryActionText}>
                        {(() => {
                          const pendingItems = filteredServiceCollecteForms.filter((item) => item.status === 'pending');
                          const allSelected = pendingItems.length > 0 && pendingItems.every((item) => selectedPendingFormIds[item.id]);
                          return allSelected
                          ? 'Tout désélectionner'
                          : 'Tout sélectionner';
                        })()}
                      </Text>
                    </TouchableOpacity>
                    <TouchableOpacity
                      style={styles.primaryButton}
                      onPress={async () => {
                        const selectedIds = Object.keys(selectedPendingFormIds).filter((id) => selectedPendingFormIds[id]);
                        if (selectedIds.length === 0) {
                          Alert.alert('Sélection requise', 'Sélectionnez au moins un formulaire à synchroniser.');
                          return;
                        }
                        const synced = await syncQueue(undefined, selectedIds);
                        if (synced) {
                          setSelectedPendingFormIds({});
                        }
                      }}
                    >
                      <Text style={styles.primaryButtonText}>Synchroniser la sélection ({selectedPendingCount})</Text>
                    </TouchableOpacity>
                  </View>
                ) : null}
              </View>
            ) : null}
          </>
        )}
      </View>
    </ScrollView>
  );

  const renderCollecteCartoForm = () => (
    <ScrollView contentContainerStyle={[styles.screenContent, { paddingBottom: 130 }]}>
      <View style={styles.topStatusBar}>
        <View style={styles.brandRow}>
          <TouchableOpacity onPress={() => { setActiveTab('collecte'); setTab('campaign'); }} style={styles.backButton}>
            <Text style={styles.backButtonText}>←</Text>
          </TouchableOpacity>
          <Image source={{ uri: `${apiBase}/images/logo-dms-cccm.avif` }} style={styles.brandLogoMini} resizeMode="contain" />
          <Text style={styles.appBrand}>Collecte</Text>
        </View>
      </View>

      <Text style={styles.formTitle}>Ajouter collecte carto service</Text>
      <View style={styles.formCardBlock}>
        {openedSavedFormStatus === 'pending' ? (
          <View style={styles.emptyQuestionCard}>
            <Text style={styles.emptyQuestionText}>Mode lecture seule: formulaire prêt à envoyer.</Text>
          </View>
        ) : null}
        <View style={styles.campaignCard}>
          <Text style={styles.campaignCardTitle}>Informations de collecte</Text>
          <Text style={styles.campaignCardHint}>Renseignez la date de collecte puis choisissez le site.</Text>
          <Text style={styles.fieldLabel}>Date de collecte (AAAA-MM-JJ)</Text>
          <TextInput
            style={[styles.input, collecteMetaErrors.date && styles.inputInvalid, openedSavedFormStatus === 'pending' && styles.inputDisabled]}
            value={dateCollecte}
            onChangeText={setDateCollecte}
            editable={openedSavedFormStatus !== 'pending'}
            placeholder="2026-08-22"
            placeholderTextColor="#94a3b8"
          />
          {collecteMetaErrors.date ? <Text style={styles.requiredFieldHint}>Date requise (format AAAA-MM-JJ).</Text> : null}
          {renderSiteCascade(openedSavedFormStatus === 'pending')}
          {collecteMetaErrors.site ? <Text style={styles.requiredFieldHint}>Site requis pour lier la collecte.</Text> : null}
        </View>

        <View style={styles.campaignDetailsCard}>
          <Text style={styles.campaignDetailsTitle}>Formulaire des services</Text>
          <Text style={styles.campaignDetailsHint}>Sélectionnez un thème pour afficher ses questions.</Text>

          {isQuestionnaireLoading ? (
            <Text style={styles.loadingBannerText}>Chargement du questionnaire...</Text>
          ) : null}

          <View style={styles.inlineActions}>
            <TouchableOpacity style={styles.secondaryActionButton} onPress={() => void loadQuestionnaire(false)}>
              <Text style={styles.secondaryActionText}>Actualiser questionnaire</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.secondaryActionButton} onPress={fillRandomCollecteData}>
              <Text style={styles.secondaryActionText}>Remplir aléatoirement (test)</Text>
            </TouchableOpacity>
          </View>

          {questionnaire ? (
            <>
              <Text style={styles.fieldLabel}>Thèmes du service</Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.campaignFilterRow}>
                {collecteMenuSections.map((section) => (
                  <TouchableOpacity
                    key={`rubrique-${section.key}`}
                    style={[
                      styles.campaignFilterChip,
                      (questionnaire ? activeQuestionnaireSection === section.key : selectedSector === section.key) && styles.campaignFilterChipActive,
                      openedSavedFormStatus === 'pending' && styles.disabledChip,
                    ]}
                    onPress={() => {
                      if (openedSavedFormStatus === 'pending') {
                        return;
                      }
                      if (questionnaire) {
                        setActiveQuestionnaireSection(section.key);
                        setActiveQuestionnaireSubgroup('__all__');
                      } else {
                        setSelectedSector(section.key as SectorKey);
                      }
                    }}
                  >
                    <Text
                      style={[
                        styles.campaignFilterChipText,
                        (questionnaire ? activeQuestionnaireSection === section.key : selectedSector === section.key) && styles.campaignFilterChipTextActive,
                      ]}
                    >
                      {section.label}
                    </Text>
                  </TouchableOpacity>
                ))}
              </ScrollView>

              <View style={styles.questionnaireHeaderCard}>
                <Text style={styles.questionnaireHeaderTitle}>
                  {activeQuestionnaireSectionMeta?.label || 'Section'}
                </Text>
                <Text style={styles.questionnaireHeaderMeta}>
                  {activeQuestionnaireQuestions.length}/{questionnaireQuestionTotal} question(s) affichée(s)
                </Text>
              </View>

              {activeQuestionnaireQuestions.length === 0 ? (
                <View style={styles.emptyQuestionCard}>
                  <Text style={styles.emptyQuestionText}>Aucune question disponible pour ce thème.</Text>
                </View>
              ) : (
                activeQuestionnaireQuestions.map((question, index) => {
                  const questionType = String(question.type || '').trim().toLowerCase();
                  const questionName = String(question.name || `question_${index + 1}`).trim();
                  const questionLabel = getQuestionLabel(question) || `Question ${index + 1}`;
                  const currentValue = String(questionnaireAnswers[questionName] ?? '');
                  const otherDetailKey = `${questionName}__autre`;
                  const otherDetailValue = String(questionnaireAnswers[otherDetailKey] ?? '');
                  const isQuestionInvalid = Boolean(invalidRequiredQuestionKeys[questionName]);
                  const isOtherDetailInvalid = Boolean(invalidRequiredQuestionKeys[otherDetailKey]);
                  const requiredFlag = String(question.required ?? '').trim().toLowerCase();
                  const isRequired = requiredFlag === 'true' || requiredFlag === '1' || requiredFlag === 'yes';
                  const isSelectOne = /^select[_ ]one\b/.test(questionType);
                  const isSelectMultiple = /^select[_ ]multiple\b/.test(questionType);

                  if (questionType === 'note') {
                    return (
                      <View key={`note-${questionName}-${index}`} style={styles.noteQuestionCard}>
                        <Text style={styles.noteQuestionLabel}>{questionLabel}</Text>
                      </View>
                    );
                  }

                  return (
                    <View key={`question-${questionName}-${index}`} style={[styles.questionFieldCard, isQuestionInvalid && styles.questionFieldCardInvalid]}>
                      <View style={styles.questionHeaderRow}>
                        <Text style={styles.questionIndexBadge}>Q{index + 1}</Text>
                        {isRequired ? <Text style={styles.requiredBadge}>Obligatoire</Text> : null}
                      </View>

                      {(isSelectOne || isSelectMultiple) ? (
                        (() => {
                          const choices = getQuestionChoices(question);
                          const options = choices.map((choice) => ({
                            value: String(choice.name || '').trim(),
                            label: getChoiceLabel(choice) || String(choice.name || '').trim(),
                            isOther: isOtherChoiceLabel(
                              String(choice.name || '').trim(),
                              getChoiceLabel(choice) || String(choice.name || '').trim(),
                            ),
                          }));
                          const selectedValues = isSelectMultiple ? parseSelectedValues(currentValue) : [];
                          const selectedLabel = isSelectOne
                            ? options.find((option) => option.value === currentValue)?.label ?? ''
                            : selectedValues.length > 0
                            ? `${selectedValues.length} sélection(s)`
                            : '';
                          const hasOtherSelected = isSelectOne
                            ? options.some((option) => option.value === currentValue && option.isOther)
                            : selectedValues.some((selectedValue) =>
                                options.some((option) => option.value === selectedValue && option.isOther),
                              );

                          return (
                            <>
                              {renderSearchablePicklist(
                                `question-${questionName}`,
                                questionLabel,
                                options.map((option) => ({ value: option.value, label: option.label })),
                                selectedLabel,
                                (value) => {
                                  updateQuestionnaireField(questionName, value);
                                  const nextValues = isSelectMultiple ? parseSelectedValues(value) : [value];
                                  const nextHasOther = nextValues.some((selectedValue) =>
                                    options.some((option) => option.value === selectedValue && option.isOther),
                                  );
                                  if (!nextHasOther) {
                                    updateQuestionnaireField(otherDetailKey, '');
                                  }
                                },
                                isSelectMultiple ? 'Choisir une ou plusieurs options' : 'Choisir une option',
                                'Aucune option disponible.',
                                isSelectMultiple,
                                selectedValues,
                                currentValue,
                                openedSavedFormStatus === 'pending',
                              )}
                              {hasOtherSelected ? (
                                <View style={styles.formFieldWrapper}>
                                  <Text style={styles.fieldLabel}>Précisez autre</Text>
                                  <TextInput
                                    style={[styles.input, isOtherDetailInvalid && styles.inputInvalid, openedSavedFormStatus === 'pending' && styles.inputDisabled]}
                                    value={otherDetailValue}
                                    onChangeText={(value) => updateQuestionnaireField(otherDetailKey, value)}
                                    editable={openedSavedFormStatus !== 'pending'}
                                    placeholder="Précisez"
                                    placeholderTextColor="#94a3b8"
                                  />
                                </View>
                              ) : null}
                            </>
                          );
                        })()
                      ) : (
                        <View style={styles.formFieldWrapper}>
                          <Text style={styles.fieldLabel}>{questionLabel}</Text>
                          <TextInput
                            style={[styles.input, openedSavedFormStatus === 'pending' && styles.inputDisabled]}
                            value={currentValue}
                            onChangeText={(value) => updateQuestionnaireField(questionName, value)}
                            editable={openedSavedFormStatus !== 'pending'}
                            keyboardType={questionType.includes('int') || questionType.includes('decimal') ? 'numeric' : 'default'}
                            multiline={questionType === 'text'}
                            placeholder={questionLabel}
                            placeholderTextColor="#94a3b8"
                          />
                        </View>
                      )}
                    </View>
                  );
                })
              )}
            </>
          ) : null}

          {!questionnaire && !isQuestionnaireLoading ? (
            sectorFields.map((field) => (
              <View key={field.key} style={styles.formFieldWrapper}>
                <Text style={styles.fieldLabel}>{field.label}</Text>
                {field.type === 'boolean' ? (
                  <View style={styles.toggleRow}>
                    {boolOptions.map((option) => (
                      <TouchableOpacity
                        key={option}
                        style={[styles.toggleButton, sectorData[field.key] === option && styles.toggleButtonActive, openedSavedFormStatus === 'pending' && styles.inputDisabled]}
                        onPress={() => updateSectorField(field.key, option)}
                        disabled={openedSavedFormStatus === 'pending'}
                      >
                        <Text style={[styles.toggleButtonText, sectorData[field.key] === option && styles.toggleButtonTextActive]}>{option}</Text>
                      </TouchableOpacity>
                    ))}
                  </View>
                ) : (
                  <TextInput
                    style={[styles.input, openedSavedFormStatus === 'pending' && styles.inputDisabled]}
                    value={String(sectorData[field.key] ?? '')}
                    onChangeText={(value) => updateSectorField(field.key, value)}
                    editable={openedSavedFormStatus !== 'pending'}
                    keyboardType={field.type === 'number' ? 'numeric' : 'default'}
                    placeholder={field.label}
                    placeholderTextColor="#94a3b8"
                  />
                )}
              </View>
            ))
          ) : null}
        </View>

        {openedSavedFormStatus === 'pending' ? (
          <View style={styles.emptyQuestionCard}>
            <Text style={styles.emptyQuestionText}>Ce formulaire est prêt à envoyer. Enregistrement et brouillon désactivés.</Text>
          </View>
        ) : (
          <View style={styles.inlineActions}>
            <TouchableOpacity style={styles.secondaryActionButton} onPress={() => saveSectorDraft('draft')}>
              <Text style={styles.secondaryActionText}>Brouillon</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.primaryButton} onPress={() => saveSectorDraft('pending')}>
              <Text style={styles.primaryButtonText}>Enregistrer</Text>
            </TouchableOpacity>
          </View>
        )}
      </View>
    </ScrollView>
  );

  const renderGeographyForm = () => (
    <ScrollView contentContainerStyle={[styles.screenContent, { paddingBottom: 110 }]}>
      <View style={styles.topStatusBar}>
        <View style={styles.brandRow}>
          <TouchableOpacity
            onPress={() => {
              if (geographyPanelMode === 'form') {
                stopPolygonTracking();
                setGeographyPanelMode('list');
                return;
              }
              setActiveTab('dashboard');
              setTab('dashboard');
            }}
            style={styles.backButton}
          >
            <Text style={styles.backButtonText}>←</Text>
          </TouchableOpacity>
          <Image source={{ uri: `${apiBase}/images/logo-dms-cccm.avif` }} style={styles.brandLogoMini} resizeMode="contain" />
          <Text style={styles.appBrand}>Géographie</Text>
        </View>
      </View>

      <Text style={styles.formTitle}>Collecte géographique</Text>

      <View style={[styles.formCardBlock, styles.geographyDashboardShell]}>
        {geographyPanelMode === 'list' ? (
          <View style={styles.campaignDetailsCard}>
            <View style={styles.geographyHeroCard}>
              <Text style={styles.geographyEyebrow}>Cartographie / sites</Text>
              <Text style={styles.geographyHeroTitle}>Tableau de bord</Text>
              <Text style={styles.geographyHeroText}>Suivez votre progression, accédez aux collectes et gérez vos zones d’intervention.</Text>
            </View>

            <View style={[styles.quickActionsCard, styles.collecteDashboardCard, styles.geographyStatsCard]}>
              <View style={styles.campaignSummaryRow}>
                <View style={[styles.campaignSummaryChip, styles.collecteSummaryChip, styles.geographySummaryChip]}>
                  <Text style={[styles.campaignSummaryValue, styles.geographySummaryValue]}>{geographySummary.synchronise}</Text>
                  <Text style={[styles.campaignSummaryLabel, styles.geographySummaryLabel]}>Synchronisé</Text>
                </View>
                <View style={[styles.campaignSummaryChip, styles.collecteSummaryChip, styles.geographySummaryChip]}>
                  <Text style={[styles.campaignSummaryValue, styles.geographySummaryValue]}>{geographySummary.en_attente}</Text>
                  <Text style={[styles.campaignSummaryLabel, styles.geographySummaryLabel]}>En attente</Text>
                </View>
                <View style={[styles.campaignSummaryChip, styles.collecteSummaryChip, styles.geographySummaryChip]}>
                  <Text style={[styles.campaignSummaryValue, styles.geographySummaryValue]}>{geographySummary.brouillon}</Text>
                  <Text style={[styles.campaignSummaryLabel, styles.geographySummaryLabel]}>Brouillon</Text>
                </View>
              </View>
            </View>

            <Text style={styles.collecteSectionTitle}>Autres actions</Text>
            <View style={styles.collecteActionGrid}>
              {[
                { key: 'Nouvelle collecte géographique', icon: '📍', tone: 'highlight' },
                { key: 'Brouillons', icon: '🗂️', tone: 'light' },
                { key: 'Pret a envoyer', icon: '📬', tone: 'light' },
                { key: 'Sync', icon: '⇄', tone: 'light' },
              ].map((action) => (
                <TouchableOpacity
                  key={`geography-${action.key}`}
                  style={[
                    styles.actionCard,
                    styles.collecteActionCard,
                    styles.geographyActionCard,
                    action.tone === 'highlight' && styles.actionCardHighlight,
                  ]}
                  onPress={() => openGeographyAction(action.key)}
                >
                  <Text style={styles.actionIcon}>{action.icon}</Text>
                  <Text style={[styles.collecteActionLabel, styles.geographyActionLabel]}>
                    {action.key === 'Brouillons'
                      ? `${action.key} (${geographySummary.brouillon})`
                      : action.key === 'Pret a envoyer'
                      ? `${action.key} (${geographySummary.en_attente})`
                      : action.key === 'Sync'
                      ? `${action.key} (${geographySummary.synchronise})`
                      : action.key}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>

          </View>
        ) : (
          <>
            {renderSiteCascade()}
            <View style={styles.inlineActions}>
              <TouchableOpacity style={styles.secondaryActionButton} onPress={() => void syncSiteReferences()}>
                <Text style={styles.secondaryActionText}>Synchroniser sites + cartographie</Text>
              </TouchableOpacity>
            </View>

            {siteSelectionMode === 'new' ? (
              <>
                <Text style={styles.fieldLabel}>Mode</Text>
                <View style={styles.toggleRow}>
                  <View style={[styles.toggleButton, styles.toggleButtonActive]}>
                    <Text style={[styles.toggleButtonText, styles.toggleButtonTextActive]}>Polygone (contour du site)</Text>
                  </View>
                </View>
                <Text style={styles.campaignCardHint}>Pour un nouveau site, seule la prise du contour est autorisée.</Text>
              </>
            ) : (
              <>
                <Text style={styles.fieldLabel}>Mode</Text>
                <View style={styles.toggleRow}>
                  {(['point', 'polygon'] as const).map((mode) => (
                    <TouchableOpacity
                      key={mode}
                      style={[styles.toggleButton, geometryType === mode && styles.toggleButtonActive]}
                      onPress={() => {
                        setGeometryType(mode);
                        if (mode === 'point') {
                          setGpsPolygonCategory('');
                          setGpsPolygonBlockName('');
                        } else {
                          setGpsPointCategory('');
                          setGpsPointOtherLabel('');
                        }
                      }}
                    >
                      <Text style={[styles.toggleButtonText, geometryType === mode && styles.toggleButtonTextActive]}>{mode === 'point' ? 'Point' : 'Polygone'}</Text>
                    </TouchableOpacity>
                  ))}
                </View>

                {geometryType === 'point' ? (
                  <>
                    {renderSearchablePicklist(
                      'gps-point-category',
                      'Catégorie du point',
                      GPS_POINT_CATEGORIES.map((category) => ({ value: category.value, label: category.label })),
                      GPS_POINT_CATEGORIES.find((category) => category.value === gpsPointCategory)?.label ?? '',
                      (value) => setGpsPointCategory((value || '') as GpsPointCategory | ''),
                      'Choisir une catégorie',
                      'Aucune catégorie trouvée.',
                      false,
                      [],
                      gpsPointCategory,
                    )}
                    {gpsPointCategory === 'autre' ? (
                      <>
                        <Text style={styles.fieldLabel}>Préciser autre</Text>
                        <TextInput
                          style={styles.input}
                          value={gpsPointOtherLabel}
                          onChangeText={setGpsPointOtherLabel}
                          placeholder="Préciser la catégorie"
                          placeholderTextColor="#94a3b8"
                        />
                      </>
                    ) : null}
                  </>
                ) : (
                  <>
                    <Text style={styles.fieldLabel}>Catégorie du polygone</Text>
                    <View style={styles.toggleRow}>
                      {GPS_POLYGON_CATEGORIES.map((category) => (
                        <TouchableOpacity
                          key={category.value}
                          style={[styles.toggleButton, gpsPolygonCategory === category.value && styles.toggleButtonActive]}
                          onPress={() => setGpsPolygonCategory(category.value)}
                        >
                          <Text style={[styles.toggleButtonText, gpsPolygonCategory === category.value && styles.toggleButtonTextActive]}>
                            {category.label}
                          </Text>
                        </TouchableOpacity>
                      ))}
                    </View>
                    {gpsPolygonCategory === 'bloc' ? (
                      <>
                        <Text style={styles.fieldLabel}>Nom du bloc</Text>
                        <TextInput
                          style={styles.input}
                          value={gpsPolygonBlockName}
                          onChangeText={setGpsPolygonBlockName}
                          placeholder="Saisir le nom du bloc"
                          placeholderTextColor="#94a3b8"
                        />
                      </>
                    ) : null}
                  </>
                )}
              </>
            )}

            <MapView
              style={styles.mapPreview}
              region={mapRegion}
              onPress={addSelectedMapPoint}
            >
              {existingSitePolygonPoints.length > 1 ? (
                <Polyline coordinates={existingSitePolygonPoints} strokeColor="#64748b" strokeWidth={2} />
              ) : null}
              {existingSitePolygonPoints.length > 2 ? (
                <Polygon
                  coordinates={existingSitePolygonPoints}
                  strokeColor="#64748b"
                  fillColor="rgba(100,116,139,0.18)"
                  strokeWidth={2}
                />
              ) : null}
              {existingSitePoint ? (
                <Marker
                  coordinate={existingSitePoint}
                  pinColor="#64748b"
                  title="Cartographie existante"
                />
              ) : null}
              {point ? <Marker coordinate={point} /> : null}
              {polygonPoints.map((coordinate, index) => (
                <Marker
                  key={`poly-pt-${index}`}
                  coordinate={coordinate}
                  pinColor="#2A87C8"
                  title={`Borne ${index + 1}`}
                />
              ))}
              {polygonPoints.length > 1 ? <Polyline coordinates={polygonPoints} strokeColor="#2A87C8" strokeWidth={2} /> : null}
              {polygonPoints.length > 2 ? <Polygon coordinates={polygonPoints} strokeColor="#2A87C8" fillColor="rgba(42,135,200,0.15)" strokeWidth={2} /> : null}
            </MapView>
            {siteSelectionMode === 'existing' && (existingSitePoint || existingSitePolygonPoints.length > 0) ? (
              <Text style={styles.campaignCardHint}>
                Cartographie existante chargée sur mobile (mode hors ligne): {existingSitePolygonPoints.length > 0 ? `${existingSitePolygonPoints.length} bornes` : 'point GPS'}.
              </Text>
            ) : null}
            {isPolygonMode ? (
              <Text style={styles.campaignCardHint}>
                {isPolygonTracking
                  ? `Suivi actif: déplacez-vous jusqu'à la prochaine borne puis appuyez sur "Ajouter borne". Bornes capturées: ${polygonPoints.length}`
                  : `Bornes capturées: ${polygonPoints.length}`}
              </Text>
            ) : null}
            {isPolygonMode && polygonPoints.length > 0 ? (
              <View style={styles.polygonBorneList}>
                {polygonPoints.map((coordinate, index) => (
                  <View key={`borne-item-${index}`} style={styles.polygonBorneChip}>
                    <Text style={styles.polygonBorneChipText}>
                      B{index + 1}: {coordinate.latitude.toFixed(5)}, {coordinate.longitude.toFixed(5)}
                    </Text>
                  </View>
                ))}
              </View>
            ) : null}

            <View style={styles.inlineActions}>
              {isPolygonMode ? (
                <TouchableOpacity
                  style={[styles.secondaryActionButton, isPolygonTracking && styles.secondaryActionButtonActive]}
                  onPress={() => {
                    if (isPolygonTracking) {
                      stopPolygonTracking();
                    } else {
                      void startPolygonTracking();
                    }
                  }}
                >
                  <Text style={[styles.secondaryActionText, isPolygonTracking && styles.secondaryActionTextActive]}>
                    {isPolygonTracking ? 'Arrêter suivi' : 'Suivi déplacement'}
                  </Text>
                </TouchableOpacity>
              ) : (
                <TouchableOpacity style={styles.secondaryActionButton} onPress={() => void getCurrentLocation()}>
                  <Text style={styles.secondaryActionText}>Position actuelle</Text>
                </TouchableOpacity>
              )}
              <TouchableOpacity style={styles.secondaryActionButton} onPress={addPointFromLocation}>
                <Text style={styles.secondaryActionText}>{isPolygonMode ? 'Ajouter borne' : 'Actualiser point'}</Text>
              </TouchableOpacity>
              {isPolygonMode ? (
                <TouchableOpacity style={styles.secondaryActionButton} onPress={removeLastPolygonBorne}>
                  <Text style={styles.secondaryActionText}>Retirer borne</Text>
                </TouchableOpacity>
              ) : null}
              <TouchableOpacity style={styles.secondaryActionButton} onPress={clearPolygon}>
                <Text style={styles.secondaryActionText}>{isPolygonMode ? 'Effacer bornes' : 'Effacer'}</Text>
              </TouchableOpacity>
            </View>

            <Text style={styles.fieldLabel}>Précision (m)</Text>
            <TextInput style={styles.input} value={errorMargin} onChangeText={setErrorMargin} keyboardType="numeric" placeholder="10" placeholderTextColor="#94a3b8" />

            <View style={styles.inlineActions}>
              <TouchableOpacity style={styles.secondaryActionButton} onPress={() => saveGeographyForm('draft')}>
                <Text style={styles.secondaryActionText}>Brouillon</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.primaryButton} onPress={() => saveGeographyForm('pending')}>
                <Text style={styles.primaryButtonText}>Enregistrer</Text>
              </TouchableOpacity>
            </View>
          </>
        )}
      </View>
    </ScrollView>
  );

  const renderMovementRecordsList = (showHeading = true) => (
    <>
      {showHeading ? <Text style={styles.collecteSectionTitle}>Mouvements enregistrés</Text> : null}
      <View style={styles.campaignTimelineList}>
        {filteredMovementForms.length === 0 ? (
          <View style={styles.emptyQuestionCard}>
            <Text style={styles.emptyQuestionText}>Aucun mouvement dans cette catégorie.</Text>
          </View>
        ) : (
          filteredMovementForms.map((item) => {
            const site = referenceSites.find((entry) => Number(entry.id) === Number(item.site_id));
            const typeLabel = MOVEMENT_TYPE_OPTIONS.find(
              (option) => option.value === item.payload?.type_mouvement,
            )?.label ?? 'Mouvement';
            return (
              <View key={item.id} style={[styles.campaignTimelineItem, item.status === 'correction' && styles.readyInvalidCard]}>
                <View style={styles.campaignTimelineHeader}>
                  <Text style={styles.campaignTimelineType}>{typeLabel}</Text>
                  <Text
                    style={[
                      styles.campaignTimelineStatus,
                      item.status === 'synced' && styles.campaignTimelineStatusSynced,
                      item.status === 'correction' && styles.readyInvalidStatus,
                    ]}
                  >
                    {item.status === 'synced'
                      ? 'Synchronisé'
                      : item.status === 'draft'
                      ? 'Brouillon'
                      : item.status === 'correction'
                      ? 'À corriger'
                      : 'Prêt à envoyer'}
                  </Text>
                </View>
                <Text style={styles.campaignTimelineMetaText}>
                  Site: {site ? `${site.nom ?? `Site ${site.id}`}${site.code_site ? ` (${site.code_site})` : ''}` : `Site ${item.site_id}`}
                </Text>
                <Text style={styles.campaignTimelineMetaText}>Ménages: {Math.abs(Number(item.payload?.menages ?? 0))}</Text>
                <Text style={styles.campaignTimelineMetaText}>Individus: {Math.abs(Number(item.payload?.individus ?? 0))}</Text>
                <Text style={styles.campaignTimelineDate}>
                  {String(item.payload?.date_mouvement ?? item.created_at ?? '').slice(0, 10)}
                </Text>
                {item.status === 'correction' && (item.sync_error_details?.length ?? 0) > 0 ? (
                  <View style={styles.correctionFieldList}>
                    {item.sync_error_details?.map((detail, index) => (
                      <View key={`${item.id}-${detail.field || detail.label}-${index}`} style={styles.correctionFieldRow}>
                        <Text style={styles.correctionFieldLabel}>{detail.label}</Text>
                        {detail.available !== null ? (
                          <Text style={styles.correctionAvailableValue}>
                            Disponible au moment de la sync : {detail.available}
                          </Text>
                        ) : (
                          <Text style={styles.correctionFieldMeta}>{detail.message}</Text>
                        )}
                        {detail.movement !== null ? (
                          <Text style={styles.correctionFieldMeta}>
                            Mouvement demandé : {detail.movement} · Solde projeté : {detail.projected}
                          </Text>
                        ) : null}
                      </View>
                    ))}
                  </View>
                ) : null}
                {item.status === 'correction' && item.sync_error && (item.sync_error_details?.length ?? 0) === 0 ? (
                  <Text style={styles.readyErrorText}>• {item.sync_error}</Text>
                ) : null}
                {item.status === 'draft' || item.status === 'correction' ? (
                  <View style={styles.inlineActions}>
                    <TouchableOpacity style={styles.secondaryActionButton} onPress={() => loadMovementForEditing(item)}>
                      <Text style={styles.secondaryActionText}>
                        {item.status === 'correction' ? 'Corriger' : 'Éditer'}
                      </Text>
                    </TouchableOpacity>
                  </View>
                ) : null}
              </View>
            );
          })
        )}
      </View>
    </>
  );

  const renderMovementForm = () => {
    const movementType = movementData.type_mouvement as MovementType;
    const typeOptions = MOVEMENT_TYPE_OPTIONS;
    const movementListTitle = movementListMode === 'correction'
      ? 'Mouvements à corriger'
      : movementListMode === 'draft'
      ? 'Brouillons'
      : movementListMode === 'synced'
      ? 'Mouvements synchronisés'
      : 'Mouvements enregistrés';
    const movementListDescription = movementListMode === 'correction'
      ? 'Corrigez les formulaires refusés ou non conformes avant de les remettre dans la file d’envoi.'
      : 'Consultez les mouvements enregistrés dans cette catégorie.';
    const movementHint = movementType === 'depart'
      ? 'Saisissez des valeurs positives : elles seront enregistrées comme sorties négatives sur le serveur.'
      : movementType === 'recensement'
      ? 'Les effectifs validés remplaceront la population actuelle du site.'
      : movementType === 'ajustement'
      ? 'Les valeurs positives augmentent les effectifs et les valeurs négatives les diminuent.'
      : 'Les effectifs validés seront ajoutés à la population actuelle du site.';

    return (
      <ScrollView contentContainerStyle={[styles.screenContent, { paddingBottom: 120 }]}>
        <View style={styles.topStatusBar}>
          <View style={styles.brandRow}>
            <TouchableOpacity
              onPress={() => {
                if (movementPanelMode === 'form') {
                  setMovementPanelMode('list');
                  return;
                }
                if (movementListMode !== 'all') {
                  setMovementListMode('all');
                  return;
                }
                setActiveTab('dashboard');
                setTab('dashboard');
              }}
              style={styles.backButton}
            >
              <Text style={styles.backButtonText}>←</Text>
            </TouchableOpacity>
            <Image source={{ uri: `${apiBase}/images/logo-dms-cccm.avif` }} style={styles.brandLogoMini} resizeMode="contain" />
            <Text style={styles.appBrand}>Mouvements</Text>
          </View>
        </View>

        <Text style={styles.formTitle}>Mouvements de population</Text>

        {movementPanelMode === 'list' ? (
          <View style={styles.campaignDetailsCard}>
            {movementListMode !== 'all' ? (
              <>
                <View style={styles.geographyHeroCard}>
                  <Text style={styles.geographyEyebrow}>Population / sites</Text>
                  <Text style={styles.geographyHeroTitle}>
                    {movementListTitle} ({filteredMovementForms.length})
                  </Text>
                  <Text style={styles.geographyHeroText}>{movementListDescription}</Text>
                </View>
                <TouchableOpacity
                  style={styles.secondaryActionButton}
                  onPress={() => setMovementListMode('all')}
                >
                  <Text style={styles.secondaryActionText}>← Tableau de bord des mouvements</Text>
                </TouchableOpacity>
                {renderMovementRecordsList(false)}
              </>
            ) : (
              <>
            <View style={styles.geographyHeroCard}>
              <Text style={styles.geographyEyebrow}>Population / sites</Text>
              <Text style={styles.geographyHeroTitle}>Tableau de bord</Text>
              <Text style={styles.geographyHeroText}>Enregistrez les arrivées, départs, recensements et ajustements, même hors connexion.</Text>
            </View>

            <View style={[styles.quickActionsCard, styles.collecteDashboardCard, styles.geographyStatsCard]}>
              <View style={styles.campaignSummaryRow}>
                <View style={[styles.campaignSummaryChip, styles.collecteSummaryChip, styles.geographySummaryChip]}>
                  <Text style={[styles.campaignSummaryValue, styles.geographySummaryValue]}>{movementSummary.synchronise}</Text>
                  <Text style={[styles.campaignSummaryLabel, styles.geographySummaryLabel]}>Synchronisé</Text>
                </View>
                <View style={[styles.campaignSummaryChip, styles.collecteSummaryChip, styles.geographySummaryChip]}>
                  <Text style={[styles.campaignSummaryValue, styles.geographySummaryValue]}>{movementSummary.en_attente}</Text>
                  <Text style={[styles.campaignSummaryLabel, styles.geographySummaryLabel]}>Prêt</Text>
                </View>
                <View style={[styles.campaignSummaryChip, styles.collecteSummaryChip, styles.geographySummaryChip]}>
                  <Text style={[styles.campaignSummaryValue, styles.geographySummaryValue]}>{movementSummary.brouillon}</Text>
                  <Text style={[styles.campaignSummaryLabel, styles.geographySummaryLabel]}>Brouillon</Text>
                </View>
                <View style={[styles.campaignSummaryChip, styles.collecteSummaryChip, styles.readyInvalidCard]}>
                  <Text style={[styles.campaignSummaryValue, styles.movementCorrectionSummaryValue]}>{movementSummary.a_corriger}</Text>
                  <Text style={[styles.campaignSummaryLabel, styles.movementCorrectionSummaryLabel]}>À corriger</Text>
                </View>
              </View>
            </View>

            <Text style={styles.collecteSectionTitle}>Actions</Text>
            <View style={styles.collecteActionGrid}>
              {[
                { key: 'Nouveau mouvement', icon: '👥', tone: 'highlight' },
                { key: 'Brouillons', icon: '🗂️', tone: 'light' },
                { key: 'Pret a envoyer', icon: '📤', tone: 'light' },
                { key: 'À corriger', icon: '⚠️', tone: 'light' },
                { key: 'Synchronisés', icon: '✅', tone: 'light' },
              ].map((action) => (
                <TouchableOpacity
                  key={`movement-${action.key}`}
                  style={[
                    styles.actionCard,
                    styles.collecteActionCard,
                    action.tone === 'highlight' && styles.actionCardHighlight,
                  ]}
                  onPress={() => openMovementAction(action.key)}
                >
                  <Text style={styles.actionIcon}>{action.icon}</Text>
                  <Text style={styles.collecteActionLabel}>
                    {action.key === 'Brouillons'
                      ? `${action.key} (${movementSummary.brouillon})`
                      : action.key === 'Pret a envoyer'
                      ? `${action.key} (${movementSummary.en_attente})`
                      : action.key === 'À corriger'
                      ? `${action.key} (${movementSummary.a_corriger})`
                      : action.key === 'Synchronisés'
                      ? `${action.key} (${movementSummary.synchronise})`
                      : action.key}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>

              </>
            )}
          </View>
        ) : (
          <View style={styles.formCardBlock}>
            {editingMovementSyncError ? (
              <View style={[styles.campaignTimelineItem, styles.readyInvalidCard]}>
                <Text style={[styles.campaignTimelineType, styles.readyErrorText]}>Échec de la dernière synchronisation</Text>
                <Text style={styles.readyErrorText}>{editingMovementSyncError}</Text>
                <Text style={styles.campaignTimelineMetaText}>
                  Corrigez les données puis enregistrez à nouveau le formulaire.
                </Text>
              </View>
            ) : null}
            {renderSiteCascade(false, false)}

            <Text style={styles.fieldLabel}>Type de mouvement *</Text>
            <View style={styles.toggleRow}>
              {typeOptions.map((option) => (
                <TouchableOpacity
                  key={option.value}
                  style={[styles.toggleButton, movementType === option.value && styles.toggleButtonActive]}
                  onPress={() => updateMovementField('type_mouvement', option.value)}
                >
                  <Text style={[styles.toggleButtonText, movementType === option.value && styles.toggleButtonTextActive]}>{option.label}</Text>
                </TouchableOpacity>
              ))}
            </View>
            <View style={styles.emptyQuestionCard}>
              <Text style={styles.emptyQuestionText}>{movementHint}</Text>
            </View>

            <Text style={styles.fieldLabel}>Date du mouvement *</Text>
            <TextInput
              style={styles.input}
              value={movementData.date_mouvement}
              onChangeText={(value) => updateMovementField('date_mouvement', value)}
              placeholder="AAAA-MM-JJ"
              placeholderTextColor="#94a3b8"
            />

            <Text style={styles.fieldLabel}>Période</Text>
            <TextInput
              style={styles.input}
              value={movementData.periode}
              onChangeText={(value) => updateMovementField('periode', value)}
              placeholder="AAAA-MM"
              placeholderTextColor="#94a3b8"
            />

            {(movementType === 'arrivee' || movementType === 'depart') && filteredMovementReasons.length > 0
              ? renderSearchablePicklist(
                  'movement-reason',
                  'Raison du mouvement',
                  filteredMovementReasons.map((reason) => ({ value: String(reason.id), label: reason.name })),
                  filteredMovementReasons.find((reason) => String(reason.id) === movementData.raison_mouvement_id)?.name ?? '',
                  (value) => updateMovementField('raison_mouvement_id', value),
                  'Choisir une raison',
                  'Aucune raison disponible.',
                  false,
                  [],
                  movementData.raison_mouvement_id,
                )
              : null}

            <Text style={styles.fieldLabel}>Nombre de ménages *</Text>
            <TextInput
              style={styles.input}
              value={movementData.menages}
              onChangeText={(value) => {
                if (/^-?\d*$/.test(value) && (movementType === 'ajustement' || !value.startsWith('-'))) {
                  updateMovementField('menages', value);
                }
              }}
              keyboardType={movementType === 'ajustement' ? 'default' : 'numeric'}
              placeholder="0"
              placeholderTextColor="#94a3b8"
            />

            <Text style={styles.collecteSectionTitle}>Répartition démographique</Text>
            <View style={styles.campaignDetailsCard}>
              {MOVEMENT_DEMOGRAPHIC_FIELDS.map((field) => (
                <View key={field.key} style={styles.formFieldWrapper}>
                  <Text style={styles.fieldLabel}>{field.label}</Text>
                  <TextInput
                    style={styles.input}
                    value={movementData[field.key]}
                    onChangeText={(value) => {
                      if (/^-?\d*$/.test(value) && (movementType === 'ajustement' || !value.startsWith('-'))) {
                        updateMovementField(field.key, value);
                      }
                    }}
                    keyboardType={movementType === 'ajustement' ? 'default' : 'numeric'}
                    placeholder="0"
                    placeholderTextColor="#94a3b8"
                  />
                </View>
              ))}
              <Text style={styles.campaignDetailsTitle}>Total individus : {movementIndividualsTotal}</Text>
              <Text style={styles.campaignDetailsHint}>Calcul automatique à partir des huit groupes d’âge et de sexe.</Text>
            </View>

            <Text style={styles.fieldLabel}>Raison ou commentaire</Text>
            <TextInput
              style={styles.input}
              value={movementData.raison}
              onChangeText={(value) => updateMovementField('raison', value)}
              placeholder="Précision complémentaire"
              placeholderTextColor="#94a3b8"
            />

            <Text style={styles.fieldLabel}>Description</Text>
            <TextInput
              style={[styles.input, { minHeight: 90, textAlignVertical: 'top' }]}
              value={movementData.description}
              onChangeText={(value) => updateMovementField('description', value)}
              placeholder="Observations"
              placeholderTextColor="#94a3b8"
              multiline
            />

            <View style={styles.inlineActions}>
              <TouchableOpacity style={styles.secondaryActionButton} onPress={() => saveMovementForm('draft')}>
                <Text style={styles.secondaryActionText}>Brouillon</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.primaryButton} onPress={() => saveMovementForm('pending')}>
                <Text style={styles.primaryButtonText}>Enregistrer</Text>
              </TouchableOpacity>
            </View>
          </View>
        )}
      </ScrollView>
    );
  };

  const renderOssatForm = () => (
    <ScrollView contentContainerStyle={[styles.screenContent, { paddingBottom: 110 }]}>
      <View style={styles.topStatusBar}>
        <View style={styles.brandRow}>
          <TouchableOpacity onPress={() => { setActiveTab('dashboard'); setTab('dashboard'); }} style={styles.backButton}>
            <Text style={styles.backButtonText}>←</Text>
          </TouchableOpacity>
          <Image source={{ uri: `${apiBase}/images/logo-dms-cccm.avif` }} style={styles.brandLogoMini} resizeMode="contain" />
          <Text style={styles.appBrand}>OSSAT</Text>
        </View>
      </View>

      <Text style={styles.formTitle}>Formulaire OSSAT</Text>

      <View style={styles.formCardBlock}>
        {renderSiteCascade(false, false)}

        {['date_collecte', 'agent_collecteur', 'source_information', 'statut'].map((key) => (
          <View key={key} style={styles.formFieldWrapper}>
            <Text style={styles.fieldLabel}>{key.replace(/_/g, ' ')}</Text>
            <TextInput
              style={styles.input}
              value={String(ossatData[key] ?? '')}
              onChangeText={(value) => updateOssatField(key, value)}
              placeholder={key.replace(/_/g, ' ')}
              placeholderTextColor="#94a3b8"
            />
          </View>
        ))}

        <View style={styles.inlineActions}>
          <TouchableOpacity style={styles.secondaryActionButton} onPress={() => void addPhoto()}>
            <Text style={styles.secondaryActionText}>Ajouter photo</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.secondaryActionButton} onPress={() => void getCurrentLocation()}>
            <Text style={styles.secondaryActionText}>GPS</Text>
          </TouchableOpacity>
        </View>

        <TouchableOpacity style={styles.primaryButton} onPress={() => void saveOssatDraft()}>
          <Text style={styles.primaryButtonText}>Enregistrer OSSAT</Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );

  const renderReadySyncScreen = () => {
    const allConformingSelected = conformingReadyForms.length > 0
      && conformingReadyForms.every((item) => selectedReadyFormIds[item.record.id]);
    const typeLabels: Record<FormRecord['type'], string> = {
      sector: 'Collecte sectorielle',
      geography: 'Collecte géographique',
      ossat: 'OSSAT',
      questionnaire: 'Carto Service',
      movement: 'Mouvement de population',
    };

    return (
      <ScrollView contentContainerStyle={styles.screenContent}>
        <View style={styles.pageHeaderRow}>
          <TouchableOpacity
            style={styles.iconButton}
            onPress={closeReadySync}
          >
            <Text style={styles.iconButtonText}>‹</Text>
          </TouchableOpacity>
          <View style={{ flex: 1 }}>
            <Text style={styles.pageTitle}>Prêt à envoyer</Text>
            <Text style={styles.pageSubtitle}>Sélectionnez uniquement les données conformes à synchroniser.</Text>
          </View>
        </View>

        <View style={styles.summaryGrid}>
          <View style={styles.summaryCard}>
            <Text style={styles.summaryValue}>{readyForms.length}</Text>
            <Text style={styles.summaryLabel}>En attente</Text>
          </View>
          <View style={styles.summaryCard}>
            <Text style={styles.summaryValue}>{conformingReadyForms.length}</Text>
            <Text style={styles.summaryLabel}>Conformes</Text>
          </View>
          <View style={styles.summaryCard}>
            <Text style={styles.summaryValue}>{readyForms.length - conformingReadyForms.length}</Text>
            <Text style={styles.summaryLabel}>À corriger</Text>
          </View>
        </View>

        {readyForms.length === 0 ? (
          <View style={styles.emptyQuestionCard}>
            <Text style={styles.emptyQuestionText}>Aucune donnée n’est prête à être envoyée.</Text>
          </View>
        ) : (
          <>
            <View style={styles.inlineActions}>
              <TouchableOpacity
                style={styles.secondaryActionButton}
                onPress={() => {
                  if (allConformingSelected) {
                    setSelectedReadyFormIds({});
                    return;
                  }
                  setSelectedReadyFormIds(
                    conformingReadyForms.reduce<Record<string, boolean>>((next, item) => {
                      next[item.record.id] = true;
                      return next;
                    }, {}),
                  );
                }}
              >
                <Text style={styles.secondaryActionText}>
                  {allConformingSelected ? 'Tout désélectionner' : 'Sélectionner les conformes'}
                </Text>
              </TouchableOpacity>
            </View>

            {readyForms.map(({ record, conformity }) => {
              const selected = Boolean(selectedReadyFormIds[record.id]);
              const site = referenceSites.find((entry) => Number(entry.id) === Number(record.site_id));
              const movementType = record.payload?.type_mouvement as MovementType | undefined;
              const movementLabel = movementType
                ? MOVEMENT_TYPE_OPTIONS.find((option) => option.value === movementType)?.label
                : null;
              return (
                <TouchableOpacity
                  key={record.id}
                  style={[
                    styles.campaignTimelineItem,
                    selected && styles.selectableCardActive,
                    !conformity.valid && styles.readyInvalidCard,
                  ]}
                  disabled={!conformity.valid}
                  onPress={() => setSelectedReadyFormIds((previous) => ({
                    ...previous,
                    [record.id]: !previous[record.id],
                  }))}
                >
                  <View style={styles.campaignTimelineHeader}>
                    <Text style={styles.campaignTimelineType}>
                      {selected ? '☑' : conformity.valid ? '☐' : '⚠'} {typeLabels[record.type]}
                    </Text>
                    <Text style={[
                      styles.campaignTimelineStatus,
                      conformity.valid ? styles.campaignTimelineStatusSynced : styles.readyInvalidStatus,
                    ]}>
                      {conformity.valid ? 'Conforme' : 'À corriger'}
                    </Text>
                  </View>
                  <Text style={styles.campaignTimelineMetaText}>
                    Site: {site ? `${site.nom ?? `Site ${site.id}`}${site.code_site ? ` (${site.code_site})` : ''}` : `Site ${record.site_id || 'non renseigné'}`}
                  </Text>
                  {record.type === 'movement' ? (
                    <>
                      <Text style={styles.campaignTimelineMetaText}>Type: {movementLabel ?? movementType}</Text>
                      <Text style={styles.campaignTimelineMetaText}>
                        Ménages: {Number(normalizeRecordForSync(record).payload?.menages ?? 0)} · Individus: {Number(normalizeRecordForSync(record).payload?.individus ?? 0)}
                      </Text>
                    </>
                  ) : null}
                  <Text style={styles.campaignTimelineDate}>
                    {String(record.payload?.date_mouvement ?? record.payload?.date_collecte ?? record.created_at ?? '').slice(0, 10)}
                  </Text>
                  {conformity.warning ? <Text style={styles.emptyQuestionText}>{conformity.warning}</Text> : null}
                  {conformity.errors.map((error) => (
                    <Text key={error} style={styles.readyErrorText}>• {error}</Text>
                  ))}
                </TouchableOpacity>
              );
            })}

            <TouchableOpacity
              style={[styles.primaryButton, (selectedReadyCount === 0 || isSyncing) && styles.readyButtonDisabled]}
              disabled={selectedReadyCount === 0 || isSyncing}
              onPress={async () => {
                const selectedIds = conformingReadyForms
                  .filter((item) => selectedReadyFormIds[item.record.id])
                  .map((item) => item.record.id);
                const synced = await syncQueue(undefined, selectedIds);
                if (synced) {
                  setSelectedReadyFormIds({});
                }
              }}
            >
              <Text style={styles.primaryButtonText}>
                {isSyncing ? 'Synchronisation…' : `Synchroniser la sélection (${selectedReadyCount})`}
              </Text>
            </TouchableOpacity>
          </>
        )}
      </ScrollView>
    );
  };

  const renderProfileScreen = () => {
    const initials = String(user?.name || 'U')
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('');

    return (
      <ScrollView contentContainerStyle={[styles.screenContent, { paddingBottom: 110 }]}>
        <View style={styles.topStatusBar}>
          <View style={styles.brandRow}>
            <Image source={{ uri: `${apiBase}/images/logo-dms-cccm.avif` }} style={styles.brandLogoMini} resizeMode="contain" />
            <Text style={styles.appBrand}>Compte</Text>
          </View>
        </View>

        <Text style={styles.formTitle}>Profil utilisateur</Text>

        <View style={styles.profileCard}>
          <View style={styles.profileAvatar}>
            <Text style={styles.profileAvatarText}>{initials || 'U'}</Text>
          </View>
          <Text style={styles.profileName}>{user?.name || 'Utilisateur'}</Text>
          <Text style={styles.profileStatus}>● Connecté</Text>

          <View style={styles.profileDetails}>
            <View style={styles.profileDetailRow}>
              <Text style={styles.profileDetailLabel}>Nom complet</Text>
              <Text style={styles.profileDetailValue}>{user?.name || '-'}</Text>
            </View>
            <View style={styles.profileDetailRow}>
              <Text style={styles.profileDetailLabel}>Adresse email</Text>
              <Text style={styles.profileDetailValue}>{user?.email || '-'}</Text>
            </View>
            <View style={styles.profileDetailRow}>
              <Text style={styles.profileDetailLabel}>Identifiant utilisateur</Text>
              <Text style={styles.profileDetailValue}>{user?.id ?? '-'}</Text>
            </View>
          </View>

          <TouchableOpacity style={styles.profileLogoutButton} onPress={handleLogout}>
            <Text style={styles.logoutText}>Se déconnecter</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    );
  };

  const currentScreen = activeTab === 'profile'
    ? renderProfileScreen()
    : tab === 'sector'
    ? renderSectorForm()
    : tab === 'campaign'
    ? renderCampaignForm()
    : tab === 'collecte_form'
    ? renderCollecteCartoForm()
    : tab === 'geography'
    ? renderGeographyForm()
    : tab === 'movement'
    ? renderMovementForm()
    : tab === 'ready_sync'
    ? renderReadySyncScreen()
    : tab === 'ossat'
    ? renderOssatForm()
    : renderDashboardScreen();

  if (!user) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <StatusBar style="dark" />
        <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={styles.container}>
          <ScrollView contentContainerStyle={styles.loginContainer}>
            <View style={styles.brandCard}>
              <View style={styles.brandBadge}>
                <Image source={{ uri: `${apiBase}/images/logo-dms-cccm.avif` }} style={styles.brandLogo} resizeMode="contain" />
              </View>
              <Text style={styles.brandName}>DMS CCCM</Text>
              <Text style={styles.brandTag}>Collecte mobile</Text>
            </View>

            <View style={styles.heroPanel}>
              <Text style={styles.heroEyebrow}>Tableau de bord terrain</Text>
              <Text style={styles.title}>Connexion OSSAT</Text>
              <Text style={styles.subtitle}>Accédez à la collecte terrain et à la synchronisation sécurisée.</Text>
            </View>

            <View style={styles.formCard}>
              <Text style={styles.label}>Email</Text>
              <TextInput style={styles.input} value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" placeholder="votre.email@dms.org" placeholderTextColor="#94a3b8" />

              <Text style={styles.label}>Mot de passe</Text>
              <TextInput style={styles.input} value={password} onChangeText={setPassword} secureTextEntry placeholder="••••••••" placeholderTextColor="#94a3b8" />

              <Text style={styles.label}>Adresse serveur</Text>
              <TextInput
                style={styles.input}
                value={serverBaseUrl}
                onChangeText={setServerBaseUrl}
                autoCapitalize="none"
                autoCorrect={false}
                placeholder="http://IP_SERVEUR:PORT"
                placeholderTextColor="#94a3b8"
              />
              <View style={styles.inlineActions}>
                <TouchableOpacity style={styles.secondaryActionButton} onPress={saveServerAddress}>
                  <Text style={styles.secondaryActionText}>Enregistrer serveur</Text>
                </TouchableOpacity>
                <TouchableOpacity style={styles.secondaryActionButton} onPress={() => void testServerAddress()}>
                  <Text style={styles.secondaryActionText}>Tester serveur</Text>
                </TouchableOpacity>
              </View>

              <TouchableOpacity style={styles.primaryButton} onPress={handleLogin}>
                <Text style={styles.primaryButtonText}>Se connecter</Text>
              </TouchableOpacity>
            </View>
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar style="dark" />
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={styles.container}>
        {currentScreen}

        <View style={styles.bottomNav}>
          {bottomNavItems.map((item) => {
            const isActive = activeTab === item.key || (item.key === 'collecte' && tab === 'sector') || (item.key === 'collecte' && tab === 'movement') || (item.key === 'collecte' && tab === 'ossat') || (item.key === 'geography' && tab === 'geography');
            return (
              <TouchableOpacity
                key={item.key}
                style={[styles.bottomNavItem, isActive && styles.bottomNavItemActive]}
                onPress={() => void handleBottomNav(item.key)}
              >
                <Text style={[styles.bottomNavIcon, isActive && styles.bottomNavIconActive]}>{item.icon}</Text>
                <Text style={[styles.bottomNavLabel, isActive && styles.bottomNavLabelActive]}>{item.label}</Text>
              </TouchableOpacity>
            );
          })}
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#f5fbff' },
  container: { flex: 1 },
  content: { padding: 18, paddingBottom: 70, backgroundColor: '#f5fbff' },
  loginContainer: { padding: 24, justifyContent: 'center', flexGrow: 1, backgroundColor: '#f3f9ff' },
  brandCard: {
    alignItems: 'center',
    marginBottom: 18,
    paddingVertical: 22,
    paddingHorizontal: 18,
    borderRadius: 28,
    backgroundColor: '#ffffff',
    shadowColor: '#2A87C8',
    shadowOpacity: 0.12,
    shadowRadius: 16,
    elevation: 4,
    borderWidth: 1,
    borderColor: '#dbeaf8',
  },
  brandBadge: {
    width: 96,
    height: 96,
    borderRadius: 26,
    backgroundColor: '#eaf6ff',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
    borderWidth: 1,
    borderColor: '#cfe8fb',
  },
  brandLogo: { width: 72, height: 72 },
  brandName: { fontSize: 28, fontWeight: '800', color: '#133053', letterSpacing: 0.4 },
  brandTag: { marginTop: 6, fontSize: 12, fontWeight: '800', color: '#2A87C8', letterSpacing: 1.2, textTransform: 'uppercase' },
  backButton: {
    width: 34,
    height: 34,
    borderRadius: 12,
    backgroundColor: '#eaf4fb',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 8,
    borderWidth: 1,
    borderColor: '#dfeaf6',
  },
  backButtonText: { fontSize: 22, color: '#133053', fontWeight: '800', lineHeight: 22 },
  heroPanel: {
    backgroundColor: '#ffffff',
    borderRadius: 20,
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: '#dfeaf6',
    marginBottom: 16,
  },
  heroEyebrow: { fontSize: 11, fontWeight: '800', color: '#2A87C8', letterSpacing: 1.2, textTransform: 'uppercase', marginBottom: 4 },
  dashboardHero: {
    backgroundColor: '#ffffff',
    borderRadius: 20,
    paddingHorizontal: 16,
    paddingVertical: 16,
    borderWidth: 1,
    borderColor: '#dfeaf6',
    marginBottom: 16,
    shadowColor: '#0f172a',
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 1,
  },
  dashboardEyebrow: { fontSize: 11, fontWeight: '800', color: '#2A87C8', letterSpacing: 1.2, textTransform: 'uppercase', marginBottom: 6 },
  dashboardTitle: { fontSize: 26, fontWeight: '800', color: '#133053' },
  dashboardSubtitle: { fontSize: 13, color: '#545456', marginTop: 4 },
  topBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#ffffff',
    borderRadius: 20,
    padding: 14,
    marginBottom: 18,
    borderWidth: 1,
    borderColor: '#dbeaf8',
    shadowColor: '#0f172a',
    shadowOpacity: 0.06,
    shadowRadius: 10,
    elevation: 2,
  },
  brandMiniWrap: {
    width: 52,
    height: 52,
    borderRadius: 16,
    backgroundColor: '#eaf6ff',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#cfe8fb',
  },
  brandMiniLogo: { width: 34, height: 34 },
  bannerContent: { flex: 1, marginLeft: 10 },
  topBannerTitle: { fontSize: 18, fontWeight: '800', color: '#133053' },
  topBannerSubtitle: { fontSize: 12, color: '#545456', marginTop: 2 },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 18 },
  title: { fontSize: 30, fontWeight: '800', color: '#133053' },
  subtitle: { fontSize: 14, color: '#545456', marginTop: 6, lineHeight: 20 },
  formTitle: { fontSize: 26, fontWeight: '800', color: '#133053', marginBottom: 12 },
  collecteIntroCard: {
    backgroundColor: '#edf6ff',
    borderWidth: 1,
    borderColor: '#cfe3f7',
    borderRadius: 16,
    paddingVertical: 12,
    paddingHorizontal: 14,
    marginBottom: 14,
  },
  collecteIntroTitle: {
    color: '#184e77',
    fontSize: 13,
    fontWeight: '800',
    marginBottom: 3,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  collecteIntroText: {
    color: '#2f5776',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '500',
  },
  collecteSectionTitle: {
    fontSize: 20,
    fontWeight: '900',
    color: '#163a5a',
    marginBottom: 4,
    marginTop: 4,
  },
  collecteSectionHint: {
    fontSize: 12,
    color: '#4f6d86',
    marginBottom: 10,
    lineHeight: 16,
  },
  collecteDashboardCard: {
    borderColor: '#cfe1f2',
    padding: 12,
    marginBottom: 14,
  },
  collecteSummaryChip: {
    backgroundColor: '#f3f9ff',
    borderColor: '#c8def1',
    borderRadius: 12,
    paddingVertical: 10,
  },
  formCardBlock: {
    backgroundColor: '#ffffff',
    borderRadius: 22,
    padding: 18,
    borderWidth: 1,
    borderColor: '#d6e5f2',
    shadowColor: '#0f172a',
    shadowOpacity: 0.06,
    shadowRadius: 12,
    elevation: 3,
  },
  fieldLabel: { fontSize: 13, color: '#1f3b57', fontWeight: '700', marginBottom: 7, textTransform: 'capitalize' },
  formFieldWrapper: { marginBottom: 14 },
  campaignSummaryRow: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 12,
  },
  campaignSummaryChip: {
    flex: 1,
    borderWidth: 1,
    borderColor: '#d9e6f2',
    borderRadius: 10,
    backgroundColor: '#f7fbff',
    paddingVertical: 8,
    paddingHorizontal: 8,
    alignItems: 'center',
  },
  campaignSummaryValue: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0f4268',
  },
  campaignSummaryLabel: {
    fontSize: 11,
    color: '#3f5d78',
    fontWeight: '700',
    marginTop: 2,
  },
  campaignCard: {
    marginBottom: 14,
    borderWidth: 1,
    borderColor: '#ccdeee',
    borderRadius: 14,
    backgroundColor: '#fbfdff',
    padding: 13,
  },
  campaignCardTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#133053',
    marginBottom: 4,
  },
  campaignCardHint: {
    fontSize: 12,
    color: '#3f5e79',
    marginBottom: 12,
    lineHeight: 17,
  },
  campaignListRow: {
    gap: 10,
    paddingBottom: 6,
    paddingRight: 6,
  },
  campaignItemCard: {
    width: 180,
    borderWidth: 1,
    borderColor: '#d5e3ef',
    borderRadius: 12,
    backgroundColor: '#ffffff',
    padding: 10,
  },
  campaignItemCardActive: {
    borderColor: '#2A87C8',
    backgroundColor: '#eaf4fb',
  },
  campaignArchivedCard: {
    opacity: 0.78,
    borderStyle: 'dashed',
  },
  campaignItemPeriod: {
    fontSize: 13,
    fontWeight: '800',
    color: '#1f4a71',
    marginBottom: 3,
  },
  campaignItemPeriodActive: {
    color: '#0f3f66',
  },
  campaignItemSite: {
    fontSize: 12,
    color: '#334e68',
    fontWeight: '600',
    minHeight: 32,
  },
  campaignItemSiteActive: {
    color: '#133a5c',
  },
  campaignItemStatus: {
    marginTop: 6,
    fontSize: 11,
    fontWeight: '800',
    color: '#1d5f95',
  },
  campaignItemStatusActive: {
    color: '#0f4a78',
  },
  campaignItemMeta: {
    marginTop: 2,
    fontSize: 11,
    color: '#5f7388',
    fontWeight: '600',
  },
  campaignItemMetaActive: {
    color: '#2f5d82',
  },
  activeCampaignBanner: {
    marginTop: 10,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: '#bcd9f0',
    backgroundColor: '#eaf4fb',
    borderRadius: 12,
    paddingVertical: 10,
    paddingHorizontal: 12,
  },
  activeCampaignBannerTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: '#0f4a78',
  },
  activeCampaignBannerText: {
    marginTop: 3,
    fontSize: 12,
    color: '#245b84',
    fontWeight: '600',
  },
  campaignDetailsCard: {
    marginTop: 10,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#d6e5f2',
    borderRadius: 12,
    backgroundColor: '#fbfdff',
    padding: 12,
  },
  campaignDetailsTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#133053',
  },
  campaignDetailsHint: {
    marginTop: 3,
    marginBottom: 10,
    fontSize: 12,
    color: '#4b647c',
  },
  geographyDashboardShell: {
    backgroundColor: '#f8fbff',
    borderColor: '#dceaf7',
  },
  geographyHeroCard: {
    backgroundColor: '#133e66',
    borderRadius: 18,
    paddingHorizontal: 14,
    paddingVertical: 14,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: '#1d5b8d',
  },
  geographyEyebrow: {
    fontSize: 10,
    fontWeight: '800',
    color: '#bfe3ff',
    textTransform: 'uppercase',
    letterSpacing: 1,
    marginBottom: 6,
  },
  geographyHeroTitle: {
    fontSize: 24,
    fontWeight: '900',
    color: '#ffffff',
    marginBottom: 4,
  },
  geographyHeroText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#d8ebff',
    lineHeight: 18,
  },
  geographyStatsCard: {
    backgroundColor: '#ffffff',
    borderColor: '#dfeaf7',
    shadowColor: '#0f172a',
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 2,
  },
  geographySummaryChip: {
    backgroundColor: '#eef7ff',
    borderColor: '#cfe6fb',
  },
  geographySummaryValue: {
    color: '#103c65',
  },
  geographySummaryLabel: {
    color: '#456989',
  },
  movementCorrectionSummaryValue: {
    color: '#991b1b',
  },
  movementCorrectionSummaryLabel: {
    color: '#b42318',
  },
  geographyActionCard: {
    backgroundColor: '#f3f9ff',
    borderColor: '#d4e5f5',
    minHeight: 102,
  },
  geographyActionLabel: {
    color: '#184b76',
  },
  campaignFilterRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 10,
  },
  campaignFilterChip: {
    borderWidth: 1,
    borderColor: '#c5d5e5',
    borderRadius: 999,
    backgroundColor: '#ffffff',
    paddingHorizontal: 10,
    paddingVertical: 7,
  },
  campaignFilterChipActive: {
    borderColor: '#2b7bb5',
    backgroundColor: '#e7f3fc',
  },
  campaignFilterChipText: {
    color: '#334e68',
    fontSize: 12,
    fontWeight: '700',
  },
  campaignFilterChipTextActive: {
    color: '#134a75',
  },
  disabledChip: {
    opacity: 0.55,
  },
  campaignTimelineList: {
    marginTop: 2,
    gap: 8,
  },
  campaignTimelineItem: {
    borderWidth: 1,
    borderColor: '#d9e6f2',
    borderRadius: 10,
    backgroundColor: '#ffffff',
    paddingVertical: 8,
    paddingHorizontal: 10,
  },
  campaignTimelineHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  pendingStatusWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  pendingSelectBox: {
    width: 22,
    height: 22,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#9fb8cf',
    backgroundColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  pendingSelectBoxActive: {
    borderColor: '#2b7bb5',
    backgroundColor: '#e7f3fc',
  },
  pendingSelectBoxText: {
    color: '#0f5a90',
    fontSize: 12,
    fontWeight: '800',
    lineHeight: 12,
  },
  campaignTimelineType: {
    fontSize: 12,
    fontWeight: '800',
    color: '#1f4a71',
  },
  campaignTimelineStatus: {
    fontSize: 11,
    fontWeight: '700',
    color: '#9a3412',
    backgroundColor: '#ffedd5',
    borderWidth: 1,
    borderColor: '#fdba74',
    borderRadius: 999,
    paddingHorizontal: 7,
    paddingVertical: 2,
  },
  campaignTimelineStatusSynced: {
    color: '#166534',
    backgroundColor: '#dcfce7',
    borderColor: '#86efac',
  },
  campaignTimelineId: {
    marginTop: 4,
    fontSize: 11,
    color: '#4b647c',
    fontWeight: '600',
  },
  campaignTimelineMetaText: {
    marginTop: 2,
    fontSize: 11,
    color: '#41576d',
    fontWeight: '600',
  },
  campaignTimelineDate: {
    marginTop: 2,
    fontSize: 11,
    color: '#6b7f93',
  },
  questionnaireHeaderCard: {
    marginTop: 12,
    marginBottom: 10,
    backgroundColor: '#f1f7fd',
    borderWidth: 1,
    borderColor: '#d6e8f6',
    borderRadius: 12,
    paddingVertical: 10,
    paddingHorizontal: 12,
  },
  questionnaireHeaderTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#133053',
  },
  questionnaireHeaderMeta: {
    marginTop: 4,
    fontSize: 12,
    color: '#48607a',
    fontWeight: '600',
  },
  questionFieldCard: {
    marginBottom: 14,
    backgroundColor: '#fbfdff',
    borderWidth: 1,
    borderColor: '#d7e7f5',
    borderRadius: 14,
    padding: 13,
  },
  questionFieldCardInvalid: {
    borderColor: '#dc2626',
    backgroundColor: '#fff5f5',
  },
  questionHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  questionIndexBadge: {
    fontSize: 11,
    fontWeight: '800',
    color: '#1d5f95',
    backgroundColor: '#ecf7ff',
    borderWidth: 1,
    borderColor: '#b9def8',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 999,
  },
  requiredBadge: {
    fontSize: 11,
    fontWeight: '700',
    color: '#9a3412',
    backgroundColor: '#ffedd5',
    borderWidth: 1,
    borderColor: '#fdba74',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 999,
  },
  questionCodeText: {
    marginTop: 8,
    fontSize: 11,
    color: '#64748b',
    fontWeight: '600',
  },
  noteQuestionCard: {
    marginBottom: 14,
    backgroundColor: '#f8fafc',
    borderWidth: 1,
    borderColor: '#d9e4ef',
    borderRadius: 12,
    paddingVertical: 10,
    paddingHorizontal: 12,
  },
  noteQuestionLabel: {
    color: '#334155',
    fontSize: 13,
    fontWeight: '600',
  },
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 12 },
  chip: { backgroundColor: '#edf6ff', borderRadius: 12, paddingHorizontal: 12, paddingVertical: 8, borderWidth: 1, borderColor: '#d6e8f6' },
  chipActive: { backgroundColor: '#2A87C8', borderColor: '#2A87C8' },
  chipText: { fontSize: 12, fontWeight: '700', color: '#133053' },
  chipTextActive: { color: '#ffffff' },
  themeBottomMenu: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#dfeaf6',
    paddingHorizontal: 8,
    paddingVertical: 8,
    marginTop: 16,
    shadowColor: '#0f172a',
    shadowOpacity: 0.04,
    shadowRadius: 10,
    elevation: 2,
  },
  themeBottomSectionsScroll: {
    flex: 1,
    marginLeft: 8,
  },
  themeBottomSectionsRow: {
    paddingRight: 8,
    alignItems: 'center',
  },
  themeBottomItem: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
    borderRadius: 14,
    minHeight: 64,
    paddingHorizontal: 8,
  },
  themeBottomHomeItem: {
    minWidth: 68,
  },
  themeBottomSectionItem: {
    minWidth: 78,
    marginRight: 8,
  },
  themeBottomItemActive: {
    backgroundColor: '#eaf4fb',
  },
  themeHomeItem: {
    backgroundColor: '#edf6ff',
  },
  themeBottomItemText: {
    fontSize: 18,
    fontWeight: '800',
    color: '#133053',
    marginBottom: 2,
  },
  themeBottomItemTextActive: {
    color: '#2A87C8',
  },
  themeBottomItemLabel: {
    fontSize: 10,
    fontWeight: '700',
    color: '#4f5d6b',
    textTransform: 'uppercase',
    letterSpacing: 0.3,
  },
  themeBottomItemLabelActive: {
    color: '#2A87C8',
  },
  toggleRow: { flexDirection: 'row', gap: 8, marginBottom: 12 },
  toggleButton: { flex: 1, backgroundColor: '#edf5ff', borderRadius: 12, paddingVertical: 10, borderWidth: 1, borderColor: '#d8ebff', alignItems: 'center' },
  toggleButtonActive: { backgroundColor: '#2A87C8', borderColor: '#2A87C8' },
  toggleButtonText: { fontSize: 13, color: '#133053', fontWeight: '700' },
  toggleButtonTextActive: { color: '#ffffff' },
  inlineActions: { flexDirection: 'row', gap: 10, marginBottom: 12 },
  secondaryActionButton: {
    flex: 1,
    backgroundColor: '#f3f8fd',
    borderRadius: 12,
    paddingVertical: 12,
    borderWidth: 1,
    borderColor: '#cfe0ee',
    alignItems: 'center',
  },
  secondaryActionButtonActive: {
    backgroundColor: '#2A87C8',
    borderColor: '#2A87C8',
  },
  secondaryActionText: { fontSize: 13, color: '#1a4f7a', fontWeight: '700' },
  secondaryActionTextActive: { color: '#ffffff' },
  polygonBorneList: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginTop: -4,
    marginBottom: 10,
  },
  polygonBorneChip: {
    borderWidth: 1,
    borderColor: '#bfdbfe',
    backgroundColor: '#eff6ff',
    borderRadius: 999,
    paddingHorizontal: 9,
    paddingVertical: 4,
  },
  polygonBorneChipText: {
    fontSize: 11,
    color: '#1e3a5f',
    fontWeight: '600',
  },
  archiveActionButton: {
    flex: 1,
    backgroundColor: '#fff7ed',
    borderRadius: 12,
    paddingVertical: 12,
    borderWidth: 1,
    borderColor: '#fed7aa',
    alignItems: 'center',
  },
  archiveActionText: {
    fontSize: 13,
    color: '#9a3412',
    fontWeight: '700',
  },
  mapPreview: { height: 220, borderRadius: 16, marginBottom: 12, overflow: 'hidden' },
  tabsRow: { flexDirection: 'row', gap: 12, marginBottom: 16 },
  tabButton: { flex: 1, backgroundColor: '#edf1f5', paddingVertical: 12, borderRadius: 14, alignItems: 'center', borderWidth: 1, borderColor: '#dfe7ee' },
  tabButtonActive: { backgroundColor: '#2A87C8', borderColor: '#2A87C8' },
  tabText: { fontWeight: '700', color: '#374151' },
  tabTextActive: { color: '#ffffff' },
  statsRow: { flexDirection: 'row', gap: 10, marginBottom: 16 },
  statCard: { flex: 1, backgroundColor: '#ffffff', borderRadius: 16, paddingVertical: 12, paddingHorizontal: 10, borderWidth: 1, borderColor: '#dfeaf6', alignItems: 'center' },
  statCardAccent: { flex: 1, backgroundColor: '#2A87C8', borderRadius: 16, paddingVertical: 12, paddingHorizontal: 10, alignItems: 'center', shadowColor: '#2A87C8', shadowOpacity: 0.26, shadowRadius: 12, elevation: 2 },
  statLabel: { fontSize: 11, fontWeight: '700', color: '#64748b', textTransform: 'uppercase', letterSpacing: 0.8 },
  statLabelLight: { fontSize: 11, fontWeight: '700', color: '#dfeefb', textTransform: 'uppercase', letterSpacing: 0.8 },
  statValue: { fontSize: 20, fontWeight: '800', color: '#133053', marginTop: 4 },
  statValueLight: { fontSize: 20, fontWeight: '800', color: '#ffffff', marginTop: 4 },
  quickActionsCard: { backgroundColor: '#ffffff', borderRadius: 20, padding: 14, borderWidth: 1, borderColor: '#dfeaf6', marginBottom: 16 },
  quickActionsTitle: { fontSize: 14, fontWeight: '800', color: '#133053', marginBottom: 12 },
  quickActionsGrid: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  quickActionButtonPrimary: { backgroundColor: '#2A87C8', borderRadius: 12, paddingVertical: 10, paddingHorizontal: 12, flex: 1, minWidth: 120, alignItems: 'center' },
  quickActionButton: { backgroundColor: '#edf7ff', borderRadius: 12, paddingVertical: 10, paddingHorizontal: 12, flex: 1, minWidth: 100, alignItems: 'center', borderWidth: 1, borderColor: '#cfe8fb' },
  quickActionTextPrimary: { color: '#ffffff', fontWeight: '800', fontSize: 12 },
  quickActionText: { color: '#2470ad', fontWeight: '700', fontSize: 12 },
  formCard: { backgroundColor: '#ffffff', borderRadius: 22, padding: 16, shadowColor: '#0f172a', shadowOpacity: 0.06, shadowRadius: 16, elevation: 3, borderWidth: 1, borderColor: '#e5edf5' },
  card: { backgroundColor: '#ffffff', borderRadius: 22, padding: 16, marginTop: 18, shadowColor: '#0f172a', shadowOpacity: 0.06, shadowRadius: 16, elevation: 3, borderWidth: 1, borderColor: '#e5edf5' },
  label: { fontSize: 13, fontWeight: '700', color: '#374151', marginBottom: 8, marginTop: 10 },
  input: {
    backgroundColor: '#f8fafc',
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#cfddea',
    paddingHorizontal: 12,
    paddingVertical: 12,
    fontSize: 15,
    color: '#111827',
  },
  inputInvalid: {
    borderColor: '#dc2626',
    backgroundColor: '#fff5f5',
  },
  inputDisabled: {
    opacity: 0.6,
  },
  requiredFieldHint: {
    marginTop: 6,
    color: '#b91c1c',
    fontSize: 12,
    fontWeight: '700',
  },
  picklistTrigger: {
    backgroundColor: '#f8fafc',
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#cfddea',
    paddingHorizontal: 12,
    paddingVertical: 10,
    minHeight: 44,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  picklistTriggerDisabled: {
    opacity: 0.6,
  },
  picklistTriggerText: { color: '#111827', fontSize: 14, fontWeight: '600', flex: 1 },
  picklistPlaceholderText: { color: '#94a3b8', fontSize: 14, fontWeight: '500', flex: 1 },
  picklistChevron: { color: '#3b5f80', fontSize: 11, marginLeft: 8, fontWeight: '700' },
  picklistBadgeRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginTop: 6 },
  picklistBadge: {
    backgroundColor: '#ecf7ff',
    borderWidth: 1,
    borderColor: '#b9def8',
    borderRadius: 999,
    paddingHorizontal: 8,
    paddingVertical: 4,
    maxWidth: '86%',
  },
  picklistBadgeText: { color: '#1d5f95', fontSize: 11, fontWeight: '700' },
  picklistBadgeCounter: {
    backgroundColor: '#2A87C8',
    borderRadius: 999,
    paddingHorizontal: 8,
    paddingVertical: 4,
  },
  picklistBadgeCounterText: { color: '#ffffff', fontSize: 11, fontWeight: '700' },
  picklistPanel: {
    backgroundColor: '#ffffff',
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#cfddea',
    marginTop: 8,
    padding: 9,
    gap: 8,
  },
  picklistPanelHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  picklistPanelHint: { color: '#64748b', fontSize: 12, fontWeight: '600' },
  picklistCloseButton: {
    backgroundColor: '#edf7ff',
    borderWidth: 1,
    borderColor: '#cfe8fb',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  picklistCloseButtonText: { color: '#2470ad', fontSize: 12, fontWeight: '700' },
  picklistSearchInput: {
    backgroundColor: '#f8fafc',
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#d7e3ee',
    paddingHorizontal: 10,
    paddingVertical: 7,
    color: '#111827',
    fontSize: 13,
  },
  picklistOptionsContainer: { minHeight: 90 },
  picklistOption: {
    minHeight: 40,
    paddingVertical: 8,
    paddingHorizontal: 10,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    backgroundColor: '#f8fafc',
    marginBottom: 5,
    justifyContent: 'center',
  },
  picklistOptionActive: { backgroundColor: '#2A87C8', borderColor: '#2A87C8' },
  picklistOptionText: { color: '#1e293b', fontWeight: '600', fontSize: 13 },
  picklistOptionTextActive: { color: '#ffffff' },
  pillWrap: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 8 },
  pill: { backgroundColor: '#eaf6ff', borderWidth: 1, borderColor: '#b9def8', borderRadius: 999, paddingHorizontal: 12, paddingVertical: 8, marginRight: 8, marginBottom: 8 },
  pillActive: { backgroundColor: '#2A87C8', borderColor: '#2A87C8' },
  pillText: { color: '#1e5d93', fontWeight: '700', textTransform: 'uppercase' },
  pillTextActive: { color: '#ffffff' },
  fieldBlock: { marginTop: 12 },
  booleanRow: { flexDirection: 'row', gap: 10, flexWrap: 'wrap' },
  booleanButton: { flex: 1, minWidth: 110, paddingVertical: 10, borderRadius: 12, alignItems: 'center', borderWidth: 1, borderColor: '#dbe4ee', backgroundColor: '#f8fafc' },
  booleanButtonActive: { backgroundColor: '#2A87C8', borderColor: '#2A87C8' },
  booleanButtonText: { color: '#111827', fontWeight: '700' },
  booleanButtonTextActive: { color: '#ffffff' },
  primaryButton: {
    backgroundColor: '#1f78b8',
    borderRadius: 14,
    paddingVertical: 15,
    alignItems: 'center',
    marginTop: 18,
    shadowColor: '#2A87C8',
    shadowOpacity: 0.22,
    shadowRadius: 12,
    elevation: 2,
  },
  primaryButtonText: { color: '#ffffff', fontSize: 15, fontWeight: '800', letterSpacing: 0.2 },
  secondaryButton: { backgroundColor: '#edf7ff', borderRadius: 12, paddingVertical: 12, alignItems: 'center', marginTop: 12, borderWidth: 1, borderColor: '#b9def8' },
  secondaryButtonText: { color: '#2470ad', fontWeight: '700' },
  profileCard: {
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 22,
    borderWidth: 1,
    borderColor: '#dfeaf6',
    padding: 20,
  },
  profileAvatar: {
    width: 82,
    height: 82,
    borderRadius: 41,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#2A87C8',
    marginBottom: 12,
  },
  profileAvatarText: { color: '#ffffff', fontSize: 28, fontWeight: '800' },
  profileName: { color: '#133053', fontSize: 22, fontWeight: '800', textAlign: 'center' },
  profileStatus: { color: '#16845b', fontSize: 13, fontWeight: '700', marginTop: 5 },
  profileDetails: {
    alignSelf: 'stretch',
    backgroundColor: '#f7fbff',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#e1edf7',
    marginTop: 20,
    paddingHorizontal: 14,
  },
  profileDetailRow: {
    borderBottomWidth: 1,
    borderBottomColor: '#e1edf7',
    paddingVertical: 13,
  },
  profileDetailLabel: { color: '#64748b', fontSize: 12, fontWeight: '700', marginBottom: 4 },
  profileDetailValue: { color: '#133053', fontSize: 15, fontWeight: '700' },
  profileLogoutButton: {
    alignSelf: 'stretch',
    alignItems: 'center',
    backgroundColor: '#9d4838',
    borderRadius: 14,
    marginTop: 20,
    paddingVertical: 14,
  },
  mapCard: { marginTop: 18, overflow: 'hidden', borderRadius: 18 },
  map: { width: '100%', height: 260, borderRadius: 16 },
  cardTitle: { fontWeight: '800', color: '#133053', fontSize: 16 },
  syncButton: { backgroundColor: '#9d4838', borderRadius: 12, paddingVertical: 12, alignItems: 'center', marginTop: 12 },
  syncButtonText: { color: '#ffffff', fontSize: 15, fontWeight: '800' },
  logoutButton: { backgroundColor: '#9d4838', borderRadius: 10, paddingHorizontal: 12, paddingVertical: 8 },
  logoutText: { color: '#fff', fontWeight: '700' },
  photoGrid: { flexDirection: 'row', flexWrap: 'wrap', marginTop: 12 },
  photoThumbnail: { width: 80, height: 80, borderRadius: 10, marginRight: 8, marginBottom: 8 },
  bottomNav: {
    position: 'absolute',
    left: 12,
    right: 12,
    bottom: 10,
    flexDirection: 'row',
    justifyContent: 'space-between',
    backgroundColor: '#ffffff',
    borderRadius: 18,
    paddingVertical: 8,
    paddingHorizontal: 8,
    borderWidth: 1,
    borderColor: '#dfeaf6',
    shadowColor: '#0f172a',
    shadowOpacity: 0.08,
    shadowRadius: 10,
    elevation: 4,
    height: 74,
  },
  bottomNavItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 6,
    borderRadius: 12,
  },
  bottomNavItemActive: {
    backgroundColor: '#eaf6ff',
  },
  bottomNavIcon: {
    fontSize: 20,
    lineHeight: 24,
  },
  bottomNavIconActive: {
    transform: [{ scale: 1.08 }],
  },
  bottomNavLabel: {
    marginTop: 2,
    fontSize: 10,
    color: '#64748b',
    fontWeight: '700',
  },
  bottomNavLabelActive: {
    color: '#2A87C8',
  },
  screenContent: {
    backgroundColor: '#f5fbff',
    paddingTop: 16,
    paddingHorizontal: 18,
    paddingBottom: 100,
  },
  pageHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 16,
  },
  iconButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#d7e6f2',
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconButtonText: {
    color: '#174e75',
    fontSize: 30,
    lineHeight: 32,
    fontWeight: '500',
  },
  pageTitle: {
    color: '#133053',
    fontSize: 23,
    fontWeight: '800',
  },
  pageSubtitle: {
    marginTop: 2,
    color: '#60758a',
    fontSize: 12,
    lineHeight: 17,
  },
  summaryGrid: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 14,
  },
  summaryCard: {
    flex: 1,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#d9e7f2',
    backgroundColor: '#ffffff',
    paddingVertical: 12,
    paddingHorizontal: 8,
    alignItems: 'center',
  },
  summaryValue: {
    color: '#174e75',
    fontSize: 22,
    fontWeight: '800',
  },
  summaryLabel: {
    marginTop: 2,
    color: '#60758a',
    fontSize: 10,
    fontWeight: '700',
  },
  selectableCardActive: {
    borderColor: '#2b7bb5',
    backgroundColor: '#eef8ff',
  },
  readyInvalidCard: {
    borderColor: '#f2b8b5',
    backgroundColor: '#fff7f6',
  },
  readyInvalidStatus: {
    color: '#991b1b',
    backgroundColor: '#fee2e2',
    borderColor: '#fca5a5',
  },
  readyErrorText: {
    marginTop: 4,
    color: '#b42318',
    fontSize: 11,
    fontWeight: '600',
  },
  correctionFieldList: {
    marginTop: 6,
    gap: 6,
  },
  correctionFieldRow: {
    borderWidth: 1,
    borderColor: '#fca5a5',
    borderRadius: 8,
    backgroundColor: '#fee2e2',
    paddingHorizontal: 8,
    paddingVertical: 6,
  },
  correctionFieldLabel: {
    color: '#991b1b',
    fontSize: 12,
    fontWeight: '800',
  },
  correctionAvailableValue: {
    marginTop: 2,
    color: '#b42318',
    fontSize: 12,
    fontWeight: '800',
  },
  correctionFieldMeta: {
    marginTop: 2,
    color: '#b42318',
    fontSize: 11,
    fontWeight: '600',
  },
  readyButtonDisabled: {
    opacity: 0.5,
  },
  topStatusBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 6,
    paddingTop: 8,
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  brandLogoMini: {
    width: 30,
    height: 30,
  },
  appBrand: {
    fontSize: 22,
    fontWeight: '800',
    color: '#133053',
    letterSpacing: 0.2,
  },
  statusRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  statusText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#1f2626',
  },
  signalGroup: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    height: 16,
    gap: 2,
    marginLeft: 4,
  },
  signalBarShort: {
    width: 4,
    height: 7,
    backgroundColor: '#1f2626',
    borderRadius: 2,
    opacity: 0.8,
  },
  signalBarMedium: {
    width: 4,
    height: 11,
    backgroundColor: '#1f2626',
    borderRadius: 2,
    opacity: 0.9,
  },
  signalBarLong: {
    width: 4,
    height: 15,
    backgroundColor: '#1f2626',
    borderRadius: 2,
  },
  avatarShell: {
    width: 38,
    height: 38,
    borderRadius: 18,
    backgroundColor: '#edf0ee',
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: 8,
    borderWidth: 1,
    borderColor: '#d7dcd9',
  },
  avatarText: {
    fontSize: 18,
    color: '#1f2626',
  },
  familyName: {
    fontSize: 12,
    fontWeight: '700',
    color: '#2d3232',
    letterSpacing: 1.1,
    textTransform: 'uppercase',
    marginBottom: 20,
  },
  screenTitle: {
    fontSize: 38,
    lineHeight: 44,
    fontWeight: '900',
    color: '#1f2626',
    marginBottom: 22,
  },
  primaryCardsRow: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 18,
  },
  primaryCardDark: {
    flex: 1,
    backgroundColor: '#2A87C8',
    borderRadius: 22,
    minHeight: 206,
    padding: 18,
    shadowColor: '#2A87C8',
    shadowOpacity: 0.18,
    shadowRadius: 10,
    elevation: 2,
  },
  cardIconShell: {
    width: 62,
    height: 62,
    borderRadius: 16,
    backgroundColor: 'rgba(255,255,255,0.15)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  cardIcon: {
    fontSize: 34,
  },
  bigCardTitle: {
    fontSize: 28,
    lineHeight: 30,
    color: '#ffffff',
    fontWeight: '800',
  },
  cardHint: {
    marginTop: 12,
    color: '#ebf8f4',
    fontSize: 15,
    fontWeight: '500',
  },
  arrowBadge: {
    position: 'absolute',
    right: 18,
    bottom: 18,
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#f5f5f4',
    alignItems: 'center',
    justifyContent: 'center',
  },
  arrowText: {
    color: '#133053',
    fontSize: 26,
    fontWeight: '800',
    lineHeight: 26,
  },
  productBar: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#eaf4fb',
    borderRadius: 16,
    paddingVertical: 16,
    paddingHorizontal: 16,
    marginBottom: 16,
  },
  productBarIcon: {
    width: 26,
    height: 26,
    borderRadius: 8,
    backgroundColor: '#dfeefb',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  productBarIconText: {
    fontSize: 16,
    color: '#2A87C8',
    fontWeight: '800',
  },
  productBarText: {
    color: '#1d2a2d',
    fontSize: 17,
    fontWeight: '700',
  },
  sectionTitle: {
    fontSize: 25,
    fontWeight: '900',
    color: '#1d2a2d',
    marginBottom: 16,
  },
  collecteActionGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 14,
    justifyContent: 'space-between',
  },
  collecteActionCard: {
    width: '47%',
    minHeight: 94,
    borderRadius: 14,
    paddingVertical: 14,
    backgroundColor: '#f4f9fd',
    borderColor: '#d4e6f4',
  },
  collecteActionLabel: {
    marginTop: 8,
    fontSize: 12,
    color: '#1d4e78',
    fontWeight: '800',
    textAlign: 'center',
    paddingHorizontal: 6,
  },
  actionGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    justifyContent: 'space-between',
  },
  actionCard: {
    width: '31%',
    backgroundColor: '#f3f8fc',
    borderRadius: 16,
    minHeight: 108,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 16,
    borderWidth: 1,
    borderColor: '#dfeaf6',
  },
  actionCardHighlight: {
    backgroundColor: '#eaf4fb',
  },
  actionIcon: {
    fontSize: 26,
    marginBottom: 10,
  },
  actionLabel: {
    fontSize: 11,
    color: '#1d2a2d',
    fontWeight: '700',
    textAlign: 'center',
  },
  subgroupFilterWrapper: {
    marginTop: 12,
    marginBottom: 8,
  },
  subgroupFilterRow: {
    gap: 8,
    paddingVertical: 5,
    paddingRight: 8,
  },
  subgroupFilterChip: {
    borderWidth: 1,
    borderColor: '#c5d5e5',
    backgroundColor: '#ffffff',
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  subgroupFilterChipActive: {
    borderColor: '#2b7bb5',
    backgroundColor: '#e7f3fc',
  },
  subgroupFilterChipText: {
    color: '#334155',
    fontSize: 12,
    fontWeight: '600',
  },
  subgroupFilterChipTextActive: {
    color: '#134a75',
  },
  loadingBannerText: {
    marginTop: 8,
    marginBottom: 10,
    fontSize: 12,
    color: '#1a4f7a',
    fontWeight: '700',
  },
  questionnaireInfoText: {
    fontSize: 12,
    color: '#365a78',
    fontWeight: '600',
    marginBottom: 6,
  },
  emptyQuestionCard: {
    marginTop: 8,
    borderWidth: 1,
    borderColor: '#d9e6f2',
    backgroundColor: '#f8fbfe',
    borderRadius: 12,
    paddingVertical: 12,
    paddingHorizontal: 12,
  },
  emptyQuestionText: {
    color: '#4b647c',
    fontSize: 12,
    fontWeight: '600',
  },
});
