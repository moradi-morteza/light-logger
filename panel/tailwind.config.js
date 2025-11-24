/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
        dark: {
          50: '#242424',
          100: '#1f1f1f',
          200: '#1a1a1a',
          300: '#161616',
          400: '#111111',
          500: '#0d0d0d',
        },
      },
      borderRadius: {
        'none': '0',
        DEFAULT: '0',
      },
    },
  },
  plugins: [],
}
