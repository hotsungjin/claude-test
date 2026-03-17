import type { CapacitorConfig } from '@capacitor/cli'

const config: CapacitorConfig = {
  appId: 'com.sulsung.mall',
  appName: '설성목장',
  webDir: 'public',
  server: {
    url: 'https://sulsung-mall.vercel.app',
    cleartext: false,
  },
  ios: {
    scheme: '설성목장',
    contentInset: 'automatic',
    preferredContentMode: 'mobile',
    backgroundColor: '#ffffff',
  },
}

export default config
