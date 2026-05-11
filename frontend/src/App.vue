<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const mobileMenuOpen = ref(false)

const switchLocale = (lang: string) => {
  locale.value = lang
  localStorage.setItem('lang', lang)
}

const exportLogs = async () => {
  try {
    const token = import.meta.env.VITE_API_TOKEN || 'tajnykluc123'
    const response = await fetch('/zz/api/logs/export', {
      method: 'GET',
      headers: { 'Authorization': `Bearer ${token}` }
    })

    if (!response.ok) throw new Error(`Chyba: ${response.status}`)

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `cas_logs_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.csv`
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    a.remove()
  } catch (error: any) {
    console.error('Export failed', error)
    alert('Nepodarilo sa exportovať logy.')
  }
}

const downloadPdf = async () => {
  try {
    const token = import.meta.env.VITE_API_TOKEN || 'tajnykluc123'
    const response = await fetch('/zz/api/documentation/pdf', {
      method: 'GET',
      headers: { 'Authorization': `Bearer ${token}` }
    })

    if (!response.ok) throw new Error(`Chyba: ${response.status}`)

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `api_dokumentacia.pdf`
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    a.remove()
  } catch (error: any) {
    console.error('PDF download failed', error)
    alert('Nepodarilo sa stiahnuť PDF dokumentáciu.')
  }
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 text-gray-800">
    <!-- Moderná navigácia -->
    <nav class="bg-gradient-to-r from-blue-700 to-blue-600 text-white shadow-lg">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-14">

          <!-- Logo -->
          <div class="text-xl font-bold tracking-tight">{{ t('nav.title') }}</div>

          <!-- Desktop links -->
          <div class="hidden md:flex items-center space-x-1">
            <RouterLink
              to="/"
              class="px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-white/20 transition"
              active-class="bg-white/30"
            >
              {{ t('nav.casCommands') }}
            </RouterLink>
            <RouterLink
              to="/kyvadlo"
              class="px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-white/20 transition"
              active-class="bg-white/30"
            >
              {{ t('nav.pendulum') }}
            </RouterLink>
            <RouterLink
              to="/gulicka"
              class="px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-white/20 transition"
              active-class="bg-white/30"
            >
              {{ t('nav.ballBeam') }}
            </RouterLink>
            <RouterLink
              to="/stats"
              class="px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-white/20 transition"
              active-class="bg-white/30"
            >
              {{ t('nav.stats') }}
            </RouterLink>
          </div>

          <!-- Desktop actions + language -->
          <div class="hidden md:flex items-center space-x-2">
            <a
              href="/zz/api/documentation"
              target="_blank"
              class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white rounded-lg text-xs font-medium transition"
            >
              {{ t('nav.documentation') }}
            </a>
            <button
              @click="downloadPdf"
              class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white rounded-lg text-xs font-medium transition"
            >
              {{ t('nav.exportPdf') }}
            </button>
            <button
              @click="exportLogs"
              class="px-3 py-1.5 bg-yellow-400 hover:bg-yellow-300 text-gray-900 rounded-lg text-xs font-semibold transition"
            >
              {{ t('nav.exportCsv') }}
            </button>

            <div class="border-l border-white/20 h-5 mx-1"></div>

            <button
              @click="switchLocale('sk')"
              :class="[
                'px-2 py-1 rounded-lg text-xs font-medium transition',
                locale === 'sk' ? 'bg-white/30' : 'hover:bg-white/20'
              ]"
            >
              SK
            </button>
            <button
              @click="switchLocale('en')"
              :class="[
                'px-2 py-1 rounded-lg text-xs font-medium transition',
                locale === 'en' ? 'bg-white/30' : 'hover:bg-white/20'
              ]"
            >
              EN
            </button>
          </div>

          <!-- Mobile: jazyky + hamburger -->
          <div class="md:hidden flex items-center space-x-2">
            <button
              @click="switchLocale('sk')"
              :class="['px-2 py-1 rounded text-xs', locale === 'sk' ? 'bg-white/30 font-bold' : 'hover:bg-white/20']"
            >
              SK
            </button>
            <button
              @click="switchLocale('en')"
              :class="['px-2 py-1 rounded text-xs', locale === 'en' ? 'bg-white/30 font-bold' : 'hover:bg-white/20']"
            >
              EN
            </button>
            <button
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="inline-flex items-center justify-center p-2 rounded-lg hover:bg-white/20 focus:outline-none"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Mobile menu -->
        <div v-if="mobileMenuOpen" class="md:hidden pb-4 pt-2 space-y-2">
          <RouterLink to="/" class="block px-3 py-2 rounded-lg hover:bg-white/20"> {{ t('nav.casCommands') }}</RouterLink>
          <RouterLink to="/kyvadlo" class="block px-3 py-2 rounded-lg hover:bg-white/20">{{ t('nav.pendulum') }}</RouterLink>
          <RouterLink to="/gulicka" class="block px-3 py-2 rounded-lg hover:bg-white/20">{{ t('nav.ballBeam') }}</RouterLink>
          <RouterLink to="/stats" class="block px-3 py-2 rounded-lg hover:bg-white/20">{{ t('nav.stats') }}</RouterLink>

          <div class="border-t border-white/20 pt-3 flex flex-wrap gap-2">
            <a
              href="/zz/api/documentation"
              target="_blank"
              class="px-3 py-2 bg-white/10 rounded-lg text-xs font-medium"
            >
              {{ t('nav.documentation') }}
            </a>
            <button
              @click="downloadPdf"
              class="px-3 py-2 bg-white/10 rounded-lg text-xs font-medium"
            >
              {{ t('nav.exportPdf') }}
            </button>
            <button
              @click="exportLogs"
              class="px-3 py-2 bg-yellow-400 text-gray-900 rounded-lg text-xs font-semibold"
            >
              {{ t('nav.exportCsv') }}
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hlavný obsah -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="bg-white/80 backdrop-blur-sm p-5 rounded-2xl shadow-md border border-gray-100">
        <RouterView />
      </div>
    </main>
  </div>
</template>

<style>
@import "tailwindcss";
</style>