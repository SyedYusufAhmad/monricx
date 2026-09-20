export const sites = {
    live: 'https://monricx.com',
    staging: 'https://lemonchiffon-kangaroo-819794.hostingersite.com',
};

export const viewports = {
    mobile: { width: 390, height: 844 },
    tablet: { width: 768, height: 1024 },
    desktop: { width: 1280, height: 720 },
    wide: { width: 1440, height: 900 },
};

// These are page archetypes. Product/category templates are verified once here;
// catalog data is checked independently so every product does not need a manual pass.
export const pages = [
    { name: 'home', path: '/' },
    { name: 'shop', path: '/shop' },
    { name: 'rings', path: '/rings' },
    { name: 'earings', path: '/earings' },
    { name: 'necklace-and-pendants', path: '/necklace-and-pendants' },
    { name: 'bracelets', path: '/bracelets' },
    { name: 'product', path: '/emerald-aura-pendant' },
    { name: 'about', path: '/about' },
    { name: 'contact', path: '/contact' },
    { name: 'faq', path: '/faq' },
    { name: 'privacy-policy', path: '/privacy-policy' },
    { name: 'refund-policy', path: '/refund-policy' },
];

export const thresholds = {
    pixelDifferencePercent: 0.5,
    pixelColorThreshold: 0.12,
    anchorDeviationPixels: 2,
};
