/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        // Primary Colour: CCCM Blue #2A87C8
        primary: {
          50: '#e8f4fb',
          100: '#c2e3f5',
          200: '#9ad2ef',
          300: '#72c1e9',
          400: '#4ea9db',
          500: '#2A87C8', // Main Primary Blue
          600: '#2470ad',
          700: '#1f5a8f',
          800: '#194571',
          900: '#133053',
        },
        // Secondary Colour 1: Gray #545456
        secondary: {
          50: '#f5f5f5',
          100: '#e8e8e9',
          200: '#d1d1d2',
          300: '#b9b9bb',
          400: '#8686a8',
          500: '#545456', // Main Gray
          600: '#4a4a4c',
          700: '#3e3e40',
          800: '#323234',
          900: '#262628',
        },
        // Secondary Colour 2: Terracotta #9d4838
        tertiary: {
          50: '#fbeae8',
          100: '#f5ccc5',
          200: '#eeada2',
          300: '#e78e7f',
          400: '#c9685a',
          500: '#9d4838', // Main Terracotta
          600: '#8d4032',
          700: '#75352a',
          800: '#5d2a22',
          900: '#451f19',
        },
        // Secondary Colour 3: Peach #d48c74
        accent: {
          50: '#fdf6f4',
          100: '#fae7e1',
          200: '#f6d8cd',
          300: '#f2c9b9',
          400: '#e3aa91',
          500: '#d48c74', // Main Peach
          600: '#be7d69',
          700: '#9f6858',
          800: '#7f5347',
          900: '#5f3e35',
        },
      },
      fontFamily: {
        sans: ['Inter', 'Helvetica Neue', 'Arial', 'sans-serif'],
        heading: ['Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
