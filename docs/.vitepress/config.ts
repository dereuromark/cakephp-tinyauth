import { defineConfig } from 'vitepress'

function unifiedSidebar() {
  return [
    {
      text: 'Getting Started',
      collapsed: false,
      items: [
        { text: 'Overview', link: '/guide/' },
        { text: '5-min Quick Start', link: '/guide/quick-start' },
        { text: 'Installation', link: '/guide/install' },
        { text: 'Configuration', link: '/guide/configuration' },
        { text: 'Custom Adapters', link: '/guide/custom-adapters' },
        { text: 'Troubleshooting', link: '/guide/troubleshooting' },
        { text: 'Upgrade Guide', link: '/guide/upgrade' },
      ],
    },
    {
      text: 'Authentication',
      collapsed: false,
      items: [
        { text: 'Setup & INI', link: '/authentication/' },
        { text: 'Impersonation', link: '/authentication/impersonation' },
        { text: 'Custom Adapter', link: '/authentication/adapter' },
      ],
    },
    {
      text: 'Authorization',
      collapsed: false,
      items: [
        { text: 'Setup & INI', link: '/authorization/' },
        { text: 'Middleware & Policy', link: '/authorization/middleware' },
        { text: 'Custom Adapter', link: '/authorization/adapter' },
        { text: 'Multi-Role', link: '/authorization/multi-role' },
      ],
    },
    {
      text: 'Helpers & Tools',
      collapsed: false,
      items: [
        { text: 'AuthUser (Component / Helper)', link: '/auth-user' },
        { text: 'AuthPanel (DebugKit)', link: '/auth-panel' },
      ],
    },
    {
      text: 'Reference',
      collapsed: true,
      items: [
        { text: 'CLI Commands', link: '/reference/cli' },
      ],
    },
  ]
}

export default defineConfig({
  title: 'cakephp-tinyauth',
  description: 'INI-based authentication and authorization for CakePHP — a thin wrapper over the official Authentication and Authorization plugins.',
  base: '/cakephp-tinyauth/',
  cleanUrls: true,
  lastUpdated: true,
  sitemap: {
    hostname: 'https://dereuromark.github.io/cakephp-tinyauth/',
  },
  head: [
    ['link', { rel: 'icon', href: '/cakephp-tinyauth/favicon.svg', type: 'image/svg+xml' }],
  ],
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Guide', link: '/guide/', activeMatch: '/guide/' },
      { text: 'Authentication', link: '/authentication/', activeMatch: '/authentication/' },
      { text: 'Authorization', link: '/authorization/', activeMatch: '/authorization/' },
      {
        text: 'Links',
        items: [
          { text: 'GitHub', link: 'https://github.com/dereuromark/cakephp-tinyauth' },
          { text: 'Packagist', link: 'https://packagist.org/packages/dereuromark/cakephp-tinyauth' },
          { text: 'Issues', link: 'https://github.com/dereuromark/cakephp-tinyauth/issues' },
          { text: 'TinyAuth Backend (admin GUI)', link: 'https://github.com/dereuromark/cakephp-tinyauth-backend' },
        ],
      },
    ],
    sidebar: {
      '/': unifiedSidebar(),
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/dereuromark/cakephp-tinyauth' },
    ],
    search: {
      provider: 'local',
    },
    editLink: {
      pattern: 'https://github.com/dereuromark/cakephp-tinyauth/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright Mark Scherer',
    },
  },
})
