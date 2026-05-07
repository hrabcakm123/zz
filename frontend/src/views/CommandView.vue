<script setup lang="ts">
import { ref } from 'vue'
import { Codemirror } from 'vue-codemirror'
import { StreamLanguage } from '@codemirror/language'
import { octave } from '@codemirror/legacy-modes/mode/octave'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const codeCommand = ref('a = 1 + 2\nb = 3\nc = a + b')
const consoleOutput = ref('')
const isExecuting = ref(false)

const extensions = [StreamLanguage.define(octave)]

const executeCommand = async () => {
  isExecuting.value = true
  const cmd = codeCommand.value.trim()

  if (!cmd) {
    consoleOutput.value = t('command.empty')
    isExecuting.value = false
    return
  }

  try {
    const token = import.meta.env.VITE_API_TOKEN || 'tajnykluc123'

    const response = await fetch('http://localhost:8000/api/cas/command', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      credentials: 'include',
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
    <h2 class="text-3xl font-extrabold text-gray-800 mb-6">{{ t('command.title') }}</h2>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
      
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('command.prompt') }}</label>
        
        <div class="border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-blue-500 transition-shadow">
          <Codemirror
            v-model="codeCommand"
            :placeholder="t('command.placeholder')"
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
        {{ isExecuting ? t('command.executing') : t('command.execute') }}
      </button>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('command.output') }}</label>
        <div class="bg-gray-900 rounded-lg p-4 h-48 overflow-y-auto">
          <pre class="text-green-400 font-mono text-sm whitespace-pre-wrap">{{ consoleOutput || t('command.empty') }}</pre>
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