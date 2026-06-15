/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "../public_html/**/*.php",
        "../public_html/**/*.html",
    ],
    safelist: [
        'bg-cyan/15', 'bg-violet/15', 'bg-pink/15', 'bg-amber/15',
        'bg-success/15', 'bg-danger/15',
        'text-cyan', 'text-violet', 'text-pink', 'text-amber',
        'text-success', 'text-danger',
        'border-cyan/30', 'border-violet/30', 'border-pink/30', 'border-amber/30',
        'border-success/30', 'border-danger/30',
        '!border-success/40', '!border-danger/40', '!border-violet/40', '!border-amber/40',
        'rotate-180',
    ],
    theme: {
        extend: {
            colors: {
                'bg':      'rgb(var(--c-bg) / <alpha-value>)',
                'bg-2':    'rgb(var(--c-bg-2) / <alpha-value>)',
                'surface': 'rgb(var(--c-surface) / <alpha-value>)',
                'border':  'rgb(var(--c-border) / <alpha-value>)',
                'cyan':    '#06B6D4',
                'violet':  '#8B5CF6',
                'pink':    '#EC4899',
                'amber':   '#F59E0B',
                'success': '#10B981',
                'danger':  '#EF4444',
                'text':    'rgb(var(--c-text) / <alpha-value>)',
                'muted':   'rgb(var(--c-muted) / <alpha-value>)',
            },
            fontFamily: {
                'display': ['Manrope', 'system-ui', 'sans-serif'],
                'sans':    ['Inter', 'system-ui', 'sans-serif'],
                'mono':    ['ui-monospace', 'SFMono-Regular', 'monospace'],
            },
            backgroundImage: {
                'brand-gradient':      'linear-gradient(135deg, #06B6D4 0%, #8B5CF6 50%, #EC4899 100%)',
                'brand-gradient-soft': 'linear-gradient(135deg, rgba(6,182,212,.15), rgba(139,92,246,.15), rgba(236,72,153,.15))',
            },
            animation: {
                'fade-up':      'fadeUp 0.5s cubic-bezier(.2,.8,.2,1) both',
                'fade-in':      'fadeIn 0.4s ease both',
                'shake':        'shake 0.4s ease-in-out',
                'aurora-float': 'auroraFloat 18s ease-in-out infinite',
                'shimmer':      'shimmer 1.4s linear infinite',
            },
            keyframes: {
                fadeUp: {
                    '0%':   { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                fadeIn: {
                    '0%':   { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                shake: {
                    '0%, 100%': { transform: 'translateX(0)' },
                    '25%':      { transform: 'translateX(-6px)' },
                    '75%':      { transform: 'translateX(6px)' },
                },
                auroraFloat: {
                    '0%, 100%': { transform: 'translate(0,0) scale(1)' },
                    '50%':      { transform: 'translate(40px, -30px) scale(1.1)' },
                },
                shimmer: {
                    '0%':   { backgroundPosition: '-1000px 0' },
                    '100%': { backgroundPosition: '1000px 0' },
                },
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
};
