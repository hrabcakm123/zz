<script setup lang="ts">
import { ref } from 'vue'
import { Codemirror } from 'vue-codemirror'
import { StreamLanguage } from '@codemirror/language'
import { octave } from '@codemirror/legacy-modes/mode/octave'

// Obsah editora
const codeCommand = ref('a = 1 + 2\nb = 3\nc = a + b')

// Výstup z backendu
const consoleOutput = ref('')
const isExecuting = ref(false)

// Nastavenie syntaxe Octave
const extensions = [StreamLanguage.define(octave)]

const executeCommand = async () => {
  isExecuting.value = true
  const cmd = codeCommand.value.trim()

  console.log('Odosielam príkaz:', cmd)

  if (!cmd) {
    consoleOutput.value = 'Chyba: príkaz je prázdny.'
    isExecuting.value = false
    return
  }

  try {
    // Token z .env – musí byť definovaný ako VITE_API_TOKEN
    const token = import.meta.env.VITE_API_TOKEN || 'tajnykluc123'
    console.log('Používam token:', token)

    const response = await fetch('http://localhost:8000/api/cas/command', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      credentials: 'include', // aby sa odoslali session cookies
      body: JSON.stringify({ command: cmd })
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.details || data.error || `HTTP ${response.status}`)
    }

    consoleOutput.value = data.output
  } catch (error: any) {
    console.error('Chyba fetch:', error)
    consoleOutput.value = `Chyba: ${error.message}`
  } finally {
    isExecuting.value = false
  }
}
</script>

<template>
  <div class="max-w-4xl mx-auto">
    <h2 class="text-3xl font-extrabold text-gray-800 mb-6">Príkazový riadok (Octave)</h2>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
      
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Zadaj príkazy (podporuje viacero riadkov):</label>
        
        <div class="border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-blue-500 transition-shadow">
          <Codemirror
            v-model="codeCommand"
            placeholder="Zadaj Octave kód sem..."
            :style="{ height: '200px' }"
            :autofocus="true"
            :indent-with-tab="true"
            :tab-size="2"
            :extensions="extensions"
          />
        </div>
      </div>

      <button 
        @click="executeCommand" 
        :disabled="isExecuting || !codeCommand.trim()"
        class="w-full mb-6 bg-gray-800 hover:bg-gray-900 text-white font-bold py-3 px-4 rounded-lg transition duration-200 disabled:bg-gray-400"
      >
        {{ isExecuting ? 'Vykonávam na serveri...' : 'Vykonať príkazy' }}
      </button>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Výstup zo servera:</label>
        <div class="bg-gray-900 rounded-lg p-4 h-48 overflow-y-auto">
          <pre class="text-green-400 font-mono text-sm whitespace-pre-wrap">{{ consoleOutput || 'Zatiaľ žiadny výstup. Spusti príkaz.' }}</pre>
        </div>
      </div>

    </div>
  </div>
</template>

<style>
.cm-editor {
  outline: none !important;
  font-family: 'Fira Code', 'Courier New', Courier, monospace;
  font-size: 14px;
}
</style>