# Build — Tailwind CSS kompilyatsiya

Bu papka **faqat dasturchi uchun**. Production server'ga **ko'chirilmaydi**.

## O'rnatish

```bash
cd build
npm install
```

## Ishlatish

```bash
npm run build       # bir martalik production build (minified)
npm run watch       # development uchun: o'zgarishlarda avtomatik build
```

Natija: `../public_html/assets/css/style.css` (~30–40 KB, minified).

## Ishlash printsipi

- `input.css` — manba (Tailwind direktivalari + custom @layer)
- `tailwind.config.js` — sozlamalar (ranglar, animatsiyalar, content scan)
- `content` — `public_html/**/*.php` fayllarini skanerlaydi va faqat **ishlatilgan** klasslarni saqlaydi (PurgeCSS singari)

## Yangi sahifa qo'shilgach

`npm run build` ni qayta ishga tushiring — yangi class'larni yangi CSS'ga qo'shib qaytaradi.
