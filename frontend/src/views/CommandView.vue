<script setup lang="ts">
import { ref } from 'vue'
import { Codemirror } from 'vue-codemirror'
import { StreamLanguage } from '@codemirror/language'
import { octave } from '@codemirror/legacy-modes/mode/octave'

// Obsah nášho editora (to, čo používateľ napíše)
const codeCommand = ref('a = 1 + 1\na + 2')

// Odpoveď z backendu
const consoleOutput = ref('')
const isExecuting = ref(false)

// Nastavíme syntax priamo na Octave
const extensions = [StreamLanguage.define(octave)]

const executeCommand = () => {
  isExecuting.value = true
  
  // Neskôr tu príde volanie cez AXIOS na tvoj backend:
  // axios.post('/api/execute', { command: codeCommand.value })
  
  // Zatiaľ to len simulujeme (Mocking), kým nemáme napojený backend
  setTimeout(() => {
    consoleOutput.value = ">> a = 2\n>> ans = 4\n\n(Simulovaná odpoveď z backendu)"
    isExecuting.value = false
  }, 800)
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
        :disabled="isExecuting || !codeCommand"
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
/* Drobné úpravy, aby CodeMirror sedel s Tailwindom a vyzeral ako kód */
.cm-editor {
  outline: none !important;
  font-family: 'Fira Code', 'Courier New', Courier, monospace;
  font-size: 14px;
}
</style>