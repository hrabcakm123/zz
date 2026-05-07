<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-800">
    <nav class="bg-blue-600 text-white shadow-md">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <div class="text-xl font-bold">CAS Simulátor</div>
          
          <div class="hidden md:flex space-x-4">
            <RouterLink to="/" class="px-3 py-2 rounded-md hover:bg-blue-700 transition active:bg-blue-800">CAS Príkazy</RouterLink>
            <RouterLink to="/kyvadlo" class="px-3 py-2 rounded-md hover:bg-blue-700 transition active:bg-blue-800">Kyvadlo</RouterLink>
            <RouterLink to="/gulicka" class="px-3 py-2 rounded-md hover:bg-blue-700 transition active:bg-blue-800">Gulička</RouterLink>
          </div>

          <div class="flex space-x-2">
            <button class="px-2 py-1 bg-blue-800 rounded hover:bg-blue-900">SK</button>
            <button class="px-2 py-1 bg-blue-500 rounded hover:bg-blue-400">EN</button>
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