---
layout: home

hero:
  name: cakephp-tinyauth
  text: INI-based Auth for CakePHP
  tagline: Define authentication and authorization rules in INI files instead of scattering allow/deny calls across every controller. A thin wrapper over the official Authentication and Authorization plugins.
  image:
    src: /logo.svg
    alt: cakephp-tinyauth
  actions:
    - theme: brand
      text: 5-min Quick Start
      link: /guide/quick-start
    - theme: alt
      text: Authentication
      link: /authentication/
    - theme: alt
      text: Authorization
      link: /authorization/
    - theme: alt
      text: View on GitHub
      link: https://github.com/dereuromark/cakephp-tinyauth

features:
  - icon: 📝
    title: All rules in INI files
    details: Whitelist public actions in auth_allow.ini, define role permissions in auth_acl.ini. Stop sprinkling allow/deny calls across controllers.
  - icon: ⚡
    title: Working auth in under 5 minutes
    details: Install the official plugins, drop in two TinyAuth components, write two short INI files. That's the whole setup.
  - icon: 🔌
    title: Plugin-friendly by default
    details: Picks up actions from every loaded plugin without modifications. No "register your plugin's controllers" step.
  - icon: 🚀
    title: Cached on first read
    details: INI files parsed once and cached. Auto-bypassed in debug mode for fast iteration; cleared on deploy.
  - icon: 🔍
    title: DebugKit Auth panel
    details: See per URL which rule matched, why a request was allowed or denied, and what role the current user has. No more guessing.
  - icon: 🧱
    title: Custom adapters
    details: Swap the INI file backend for a database-driven one or your own. The TinyAuthBackend plugin ships a ready-made GUI for this.
---
