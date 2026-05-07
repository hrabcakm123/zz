<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const switchLocale = (lang: string) => {
  locale.value = lang
  localStorage.setItem('lang', lang)
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-800">
    <nav class="bg-blue-600 text-white shadow-md">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <div class="text-xl font-bold">{{ t('nav.title') }}</div>
          
          <div class="hidden md:flex space-x-4">
            <RouterLink to="/" class="px-3 py-2 rounded-md hover:bg-blue-700 transition active:bg-blue-800">
              {{ t('nav.casCommands') }}
            </RouterLink>
            <RouterLink to="/kyvadlo" class="px-3 py-2 rounded-md hover:bg-blue-700 transition active:bg-blue-800">
              {{ t('nav.pendulum') }}
            </RouterLink>
            <RouterLink to="/gulicka" class="px-3 py-2 rounded-md hover:bg-blue-700 transition active:bg-blue-800">
              {{ t('nav.ballBeam') }}
            </RouterLink>
          </div>

          <div class="flex space-x-2">
            <button
              @click="switchLocale('sk')"
              :class="[
                'px-2 py-1 rounded',
                locale === 'sk' ? 'bg-blue-800 font-bold' : 'bg-blue-500 hover:bg-blue-400'
              ]"
            >
              SK
            </button>
            <button
              @click="switchLocale('en')"
              :class="[
                'px-2 py-1 rounded',
                locale === 'en' ? 'bg-blue-800 font-bold' : 'bg-blue-500 hover:bg-blue-400'
              ]"
            >
              EN
            </button>
          </div>
        </div>
      </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
        <RouterView />
      </div>
    </main>
  </div>
</template>

<style>
@import "tailwindcss";
</style>