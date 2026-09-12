import { createClient } from '@supabase/supabase-js'
const runtimeConfig = typeof window !== 'undefined' ? window.__SUPABASE_CONFIG__ : undefined
const url = import.meta.env.VITE_SUPABASE_URL || runtimeConfig?.url
const key = import.meta.env.VITE_SUPABASE_ANON_KEY || runtimeConfig?.anonKey
export const supabase = url && key ? createClient(url, key) : null
