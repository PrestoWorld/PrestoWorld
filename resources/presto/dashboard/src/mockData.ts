import { WPPost, WPActivity, WPPlugin } from './types';

export const initialPosts: WPPost[] = [
  {
    id: 1,
    title: "Introducing SolidJS and Reactive Paradigms",
    author: "admin",
    category: "Development",
    status: "Published",
    date: "2026-06-12 10:30",
    commentsCount: 12
  },
  {
    id: 2,
    title: "Optimizing Web Vitals with PurgeCSS in WordPress",
    author: "fuctonoc",
    category: "Performance",
    status: "Published",
    date: "2026-06-14 14:15",
    commentsCount: 5
  },
  {
    id: 3,
    title: "WooCommerce Modular Architectural Blueprint",
    author: "admin",
    category: "E-Commerce",
    status: "Scheduled",
    date: "2026-06-20 08:00",
    commentsCount: 0
  },
  {
    id: 4,
    title: "Designing the Ultimate Admin Control Console",
    author: "editor_lee",
    category: "Design",
    status: "Draft",
    date: "2026-06-15 11:22",
    commentsCount: 2
  },
  {
    id: 5,
    title: "Securing Headless WordPress: CORS and JWT Best Practices",
    author: "fuctonoc",
    category: "Security",
    status: "Published",
    date: "2026-06-10 16:45",
    commentsCount: 18
  }
];

export const initialPlugins: WPPlugin[] = [
  {
    id: "solid-reactive",
    name: "SolidReactive Speed Booster",
    desc: "Bypasses standard runtime rendering with reactive virtual streams. Renders lists at near assembly-level speeds.",
    version: "1.4.2",
    author: "Solid Ecosystem Lab",
    active: true,
    updateAvailable: true,
    category: "Performance"
  },
  {
    id: "purge-wp",
    name: "PurgeCSS WP Customizer",
    desc: "Injects deep css scanning across posts, plugins, and templates to automatically strip dead styling rules on every page load.",
    version: "3.2.0",
    author: "FullHuman Lab",
    active: true,
    updateAvailable: false,
    category: "Optimization"
  },
  {
    id: "yoast-seo",
    name: "Yoast SEO Compact",
    desc: "Complete search engine optimization toolkit for posts, taxonomy, semantic mapping, and XML sitemaps.",
    version: "22.5",
    author: "Team Yoast",
    active: false,
    updateAvailable: false,
    category: "Marketing"
  },
  {
    id: "akismet",
    name: "Akismet Anti-Spam",
    desc: "Used by millions, Akismet checks your comments and contact form submissions against our global database of spam to prevent spam.",
    version: "5.3",
    author: "Automattic",
    active: true,
    updateAvailable: false,
    category: "Security"
  },
  {
    id: "advanced-custom-fields",
    name: "Advanced Custom Fields Starter",
    desc: "Customize WordPress edit screens with rich fields, custom metadata builders, and block design configurations.",
    version: "6.2.7",
    author: "WP Engine",
    active: false,
    updateAvailable: true,
    category: "Development"
  }
];

export const initialActivities: WPActivity[] = [
  {
    id: 1,
    text: "New comment submitted on 'Introducing SolidJS'",
    time: "4 minutes ago",
    type: "comment"
  },
  {
    id: 2,
    text: "Draft post auto-saved: 'Designing the Ultimate Admin Control Console'",
    time: "12 minutes ago",
    type: "post"
  },
  {
    id: 3,
    text: "SolidReactive Speed Booster plugin update detected (v1.4.2)",
    time: "1 hour ago",
    type: "update"
  },
  {
    id: 4,
    text: "Attempted login blocked from malicious IP (198.51.100.42)",
    time: "2 hours ago",
    type: "security"
  },
  {
    id: 5,
    text: "Site health scan passed: 99% optimization efficiency",
    time: "4 hours ago",
    type: "update"
  }
];
