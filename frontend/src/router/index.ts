import { createRouter, createWebHistory } from 'vue-router'
// 1. Importujeme naše nové podstránky
import CommandView from '../views/CommandView.vue'
import PendulumView from '../views/PendulumView.vue'
import BallBeamView from '@/views/BallBeamView.vue'
import StatsView from '@/views/StatsView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  // 2. Vložíme ich do poľa routes
  routes: [
    {
      path: '/',
      name: 'commands',
      component: CommandView
    },
    {
      path: '/kyvadlo',
      name: 'pendulum',
      component: PendulumView
    },
        {
      path: '/gulicka',
      name: 'ballbeam',
      component: BallBeamView
    },
        {
      path: '/stats',
      name: 'stats',
      component: StatsView
    }
  ],
})

export default router