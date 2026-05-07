import { createI18n } from 'vue-i18n'
import sk from './sk'
import en from './en'

const i18n = createI18n({
  legacy: false,
  locale: localStorage.getItem('lang') || 'sk',
  fallbackLocale: 'sk',
  messages: { sk, en },
})

export default i18n