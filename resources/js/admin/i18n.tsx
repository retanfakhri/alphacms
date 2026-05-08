import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import ae from './locales/ae';

const resources = {
    ae: {
        translation: ae,
    },
    en: {
        translation: {
            "Dashboard": "Dashboard",
            // ... add more if needed
        }
    }
};

i18n
    .use(LanguageDetector)
    .use(initReactI18next)
    .init({
        resources,
        lng: 'ae', // Default language
        fallbackLng: 'ae',
        interpolation: {
            escapeValue: false,
        },
    });

export default i18n;
