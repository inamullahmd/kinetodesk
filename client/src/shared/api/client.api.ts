import axios from 'axios'

const apiBaseUrl = import.meta.env.VITE_API_URL || ''

const apiClient = axios.create({
  baseURL: `${apiBaseUrl}/api`,
  headers: {
    Accept: 'application/json',
  },
})

export default apiClient