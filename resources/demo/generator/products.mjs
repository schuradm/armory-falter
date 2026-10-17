import { svg, shadow, W, H, FLOOR } from './common.mjs';
import { pistol } from './pistol.mjs';
import { rifle } from './rifle.mjs';
import { scope, redDot } from './scope.mjs';
import { ammoBox, cartridgeSide, cartridgeUp, knife, sheath, cleaningKit, pistolCase, powderCan, jacket } from './misc.mjs';

/** Produktgruppe mit dezentem Streulicht-Saum dahinter. */
export function obj(inner, id = 'obj') {
    return `<use href="#${id}" filter="url(#halo)"/><g id="${id}">${inner}</g>`;
}

/** Pistole mittig platzieren (Begrenzungsrahmen in mm → Breite in px). */
function placePistol(o, widthPx = 1320) {
    const L = o.slideLen ?? 186;
    const S = o.slideH ?? 28;
    const x0 = -(o.barrelOut ?? 0) - 1;
    const x1 = L + 4 + (o.backSlant ?? 22) + (o.hammer ? 9 : 0);
    const y0 = -6;
    const y1 = S + (o.gripH ?? 100) + (o.magwell ? 11 : 9);
    const s = widthPx / (x1 - x0);
    const tx = (W - widthPx) / 2 - x0 * s;
    const ty = (H - (y1 - y0) * s) / 2 - y0 * s - 40;
    const bottom = ty + y1 * s;
    return shadow(W / 2 + 60, bottom + 45, widthPx * 0.4, 28, 0.9)
        + obj(`<g transform="translate(${tx} ${ty}) scale(${s})">${pistol(o)}</g>`);
}

/** Langwaffe mittig platzieren. */
function placeRifle(o, widthPx = 1840) {
    const rx1 = (o.receiverX ?? 330) + (o.receiverLen ?? 230);
    const x0 = -20;
    const x1 = rx1 + (o.barrelLen ?? 560) + (o.muzzleCap ? 16 : 4);
    const ro = o.scope ? (o.scope.obj ?? 56) / 2 + 5 : 0;
    const y0 = o.scope ? (o.scope.y ?? -58) - Math.max(ro, 45) : -30;
    const y1 = o.stock === 'shotgun' ? 180 : (o.toe ?? 150) + 4;
    const s = widthPx / (x1 - x0);
    const tx = (W - widthPx) / 2 - x0 * s;
    const ty = (H - (y1 - y0) * s) / 2 - y0 * s - 10;
    const bottom = ty + y1 * s;
    return shadow(W / 2, bottom + 34, widthPx * 0.46, 22, 0.85)
        + obj(`<g transform="translate(${tx} ${ty}) scale(${s})">${rifle(o)}</g>`);
}

const bodies = {
    'glock-17-gen5-fs': () => (placePistol({ slideLen: 186, serrations: 'vertical', guard: 'square', railSlots: 1, rearSerrLen: 30 })),
    'heckler-koch-sfp9-sf': () => (placePistol({ slideLen: 186, serrations: 'hk', slideNose: 'taper', guard: 'round', railSlots: 3, gripSlant: 21, backSlant: 16,
        frameFill: 'url(#polymerG)', texture: 'ribMM', rearSerrLen: 28, frontSerrLen: 16, beaver: 12 })),
    'walther-pdp-full-size-45': () => (placePistol({ slideLen: 194, serrations: 'terrain', slideRear: 'angled', optic: true, guard: 'square', railSlots: 3,
        rearSerrLen: 38, frontSerrLen: 30, texture: 'pyramidMM', gripSlant: 23 })),
    'sig-sauer-p320-x-five': () => (placePistol({ slideLen: 205, slideFill: 'url(#stainless)', serrations: 'vertical', serrStep: 2.6, serrSlant: -2, optic: true, guard: 'square',
        railSlots: 4, dustFront: 5, magwell: true, fiber: '#9d4640', gripW: 56, gripSlant: 22, rearSerrLen: 34, frontSerrLen: 24, triggerFill: 'url(#anod)' })),
    'cz-shadow-2': () => (placePistol({ slideLen: 196, slideH: 25, slideFill: 'url(#blued)', frameFill: 'url(#blued)', frameRails: true, hammer: true, barrelOut: 3,
        guard: 'round', gripPanel: 'url(#anod)', texture: 'pyramidMM', textureOpacity: 0, dustFront: 3, fiber: '#c9ccd0', decocker: true, takedown: false,
        serrations: 'vertical', serrStep: 2.4, frontSerr: false, rearSerrLen: 26, triggerFill: 'url(#steel)', gripSlant: 20, backSlant: 14, gripH: 98, beaver: 8,
        magRelFill: 'url(#steel)', pins: [[150, 33], [176, 34], [100, 34]] })),
    'blaser-r8-professional-success': () => (placeRifle({ stock: 'thumbhole', stockFill: 'url(#olive)', inlays: true, action: 'straight', receiverLen: 215,
        barrelLen: 580, barrelR0: 11, barrelR1: 8.5, muzzleCap: true, comb: 20, toe: 152,
        scope: { len: 350, obj: 50, eye: 44, illum: true, mounts: [138, 226], x: 300, y: -46 } })),
    'sauer-100-classic-xt': () => (placeRifle({ stock: 'classic', stockFill: 'url(#polymerG)', inlays: true, action: 'turn', mag: 'short',
        barrelLen: 560, scope: { len: 362, obj: 56, eye: 44, mounts: [140, 228], x: 296, y: -48 } })),
    'merkel-helix-explorer': () => (placeRifle({ stock: 'classic', stockFill: 'url(#polymer)', inlays: true, action: 'straight', receiverLen: 200,
        barrelLen: 510, barrelR0: 10.5, barrelR1: 8, comb: 18, toe: 146, gripDrop: 128, forendEnd: 700,
        scope: { len: 285, obj: 24, eye: 44, objLen: 40, bellLen: 10, illum: true, accentDot: true, mounts: [136, 216], x: 300, y: -40 } })),
    'cz-457-varmint': () => (placeRifle({ stock: 'classic', stockFill: 'url(#beech)', action: 'rimfire', mag: 'short', receiverLen: 175, receiverTop: -12, receiverBottom: 11,
        barrelLen: 525, barrelR0: 11.5, barrelR1: 11, comb: 16, forendBottom: 56, magBottom: 68, toe: 144, checkering: true, forendEnd: 690, boltKnob: 'ballGrad' })),
    'beretta-694-sporting': () => (placeRifle({ stock: 'shotgun', stockFill: 'url(#walnut)', ou: true, receiverFill: 'url(#stainless)', receiverLen: 125,
        barrelLen: 760, forendEnd: 760, triggerX: 300, swivels: false, action: 'none' })),
    'zeiss-conquest-v4-3-12x56': () => placeScope({ len: 360, obj: 56, eye: 44, illum: true, lensEnd: true }),
    'swarovski-z8i-2-16x50-p-l': () => placeScope({ len: 352, obj: 50, eye: 42, rail: true, turretH: 18, turretW: 26, windR: 13, lensEnd: true, powerLen: 46 }),
    'sig-sauer-romeo5-red-dot': () => shadow(W / 2 + 20, 1110, 520, 30, 0.9)
        + obj(`<g transform="translate(${W / 2 - 34 * 13} ${560}) scale(13)">${redDot()}</g>`),
    'sig-sauer-elite-ball-9mm-luger-124-gr-fmj-50-stuck': () => {
        const b = { x: 560, y: 840, w: 720, h: 330, dx: 160, dy: -170 };
        return shadow(1000, FLOOR - 4, 560, 26, 0.9)
            + obj(ammoBox({ ...b, cols: 10, rows: 5, kind: '9mm', title: '9 mm LUGER', sub: '124 GR · FMJ · VOLLMANTEL', count: '50 PATRONEN', note: 'SPORT · TRAINING' }), 'box')
            + lying(470, 1290, 10, -4, '9mm') + lying(820, 1330, 10, 3, '9mm') + lying(1330, 1300, 10, -9, '9mm');
    },
    'blaser-cdc-308-win-110-g-20-stuck': () => {
        const b = { x: 470, y: 760, w: 600, h: 410, dx: 150, dy: -150 };
        return shadow(900, FLOOR - 4, 520, 26, 0.9)
            + obj(ammoBox({ ...b, cols: 5, rows: 4, kind: '308', title: '.308 WIN', sub: '11,0 g · BLEIFREI', count: '20 PATRONEN', note: 'JAGD', lidH: 300 }), 'box')
            + standing(1430, 1236, 7.4) + standing(1560, 1250, 7.4) + lying(640, 1330, 8, -2, '308');
    },
    'walther-bwk-2-jagdmesser': () => shadow(1000, 1060, 700, 24, 0.7) + shadow(1000, 920, 760, 28, 0.6)
        + obj(`<g transform="translate(330 860) rotate(-4) scale(7.2)">${sheath()}</g><g transform="translate(250 600) scale(6.9)">${knife()}</g>`),
    'walther-universal-reinigungsset': () => cleaningKit(1000, 760),
    'heckler-koch-pistolenkoffer': () => pistolCase(1000, 1120),
    'treibladungspulver-n140-500-g': () => powderCan(1000, 1170),
    'blaser-vintage-jacke-herren': () => shadow(1000, 1310, 520, 26, 0.6) + jacket(1000, 290),
};

/** Zielfernrohr freistehend. */
function placeScope(sc, widthPx = 1640) {
    const len = sc.len ?? 360;
    const ro = (sc.obj ?? 56) / 2 + 5;
    const s = widthPx / (len + ro * 0.2);
    const cy = 690;
    return shadow(W / 2, cy + ro * s + 150, widthPx * 0.45, 26, 0.85)
        + obj(`<g transform="translate(${(W - widthPx) / 2} ${cy}) scale(${s})">${scope(sc)}</g>`);
}

/** Lose Patrone liegend mit Kontaktschatten. */
function lying(x, y, s, rot, kind) {
    const L = kind === '9mm' ? 29.7 : 71;
    return `<ellipse cx="${x + (L * s) / 2}" cy="${y + 4.5 * s}" rx="${(L * s) / 2}" ry="${1.6 * s}" fill="#000" opacity=".6" filter="url(#blur6)"/>`
        + `<g transform="translate(${x} ${y}) rotate(${rot}) scale(${s})">${cartridgeSide(kind)}</g>`;
}

/** Stehende Büchsenpatrone. */
function standing(x, y, s) {
    return `<ellipse cx="${x}" cy="${y}" rx="${7 * s}" ry="${1.8 * s}" fill="#000" opacity=".7" filter="url(#blur6)"/>`
        + `<g transform="translate(${x} ${y}) scale(${s})">${cartridgeUp('308')}</g>`;
}

/** Detailansicht: Ausschnitt eines Motivs vergrößert. */
function detail(slug, z, fx, fy) {
    return svg(`<g transform="translate(${W / 2} ${H / 2}) scale(${z}) translate(${-fx} ${-fy})">${bodies[slug]()}</g>`);
}

export const products = Object.fromEntries(Object.entries(bodies).map(([k, f]) => [k, () => svg(f())]));
products['glock-17-gen5-fs-detail'] = () => detail('glock-17-gen5-fs', 2.1, 1250, 640);
products['blaser-r8-professional-success-detail'] = () => detail('blaser-r8-professional-success', 2.3, 820, 680);
products['zeiss-conquest-v4-3-12x56-detail'] = () => detail('zeiss-conquest-v4-3-12x56', 1.9, 1180, 680);
products['walther-pdp-full-size-45-detail'] = () => detail('walther-pdp-full-size-45', 2.1, 800, 560);
