import { lines, rim } from './common.mjs';
import { scope } from './scope.mjs';

/**
 * Langwaffe in Seitenansicht (rechte Seite, Kammerstängel sichtbar), Schaftkappe links, Mündung rechts.
 * Maße in mm, Ursprung: Schaftende / Seelenachse (y = 0, y nach unten).
 */
export function rifle(o) {
    const stock = o.stock ?? 'classic';
    const sf = o.stockFill ?? 'url(#polymerG)';
    const rx0 = o.receiverX ?? 330; // Systemhülse hinten
    const rLen = o.receiverLen ?? 230;
    const rx1 = rx0 + rLen;
    const bLen = o.barrelLen ?? 560;
    const muzzle = rx1 + bLen;
    const br0 = o.barrelR0 ?? 10.5; // Laufradius am Patronenlager
    const br1 = o.barrelR1 ?? 8; // Laufradius an der Mündung
    const fe = o.forendEnd ?? rx1 + 170;
    const out = [];
    const isWood = sf.includes('walnut') || sf.includes('beech');

    // --- Lauf -------------------------------------------------------------
    if (o.ou) {
        // Bockflinte: zwei Läufe übereinander, Laufschiene oben, Mittelschiene
        const r = 10;
        out.push(`<path d="M ${rx1 - 5},${-r - 1} L ${muzzle},${-r + 1} L ${muzzle},${r - 1} L ${rx1 - 5},${r + 1} Z" fill="url(#blued)"/>`);
        out.push(`<path d="M ${rx1 - 5},${r + 1} L ${muzzle},${r - 1} L ${muzzle},${3 * r - 3} L ${rx1 - 5},${3 * r + 1} Z" fill="url(#blued)"/>`);
        // Mittelschiene (belüftet)
        out.push(`<path d="M ${rx1},${r - 3} L ${muzzle - 4},${r - 4} L ${muzzle - 4},${r + 4} L ${rx1},${r + 5} Z" fill="#0d0e10"/>`);
        for (let x = rx1 + 30; x < muzzle - 30; x += 46) out.push(`<rect x="${x}" y="${r - 3}" width="30" height="6" rx="2" fill="#2b2a28" opacity=".85"/>`);
        // Laufschiene oben (belüftet)
        out.push(`<path d="M ${rx1 - 6},${-r - 9} L ${muzzle - 2},${-r - 6} L ${muzzle - 2},${-r - 3} L ${rx1 - 6},${-r - 3} Z" fill="url(#blued)"/>`);
        for (let x = rx1 + 8; x < muzzle - 16; x += 22) out.push(`<rect x="${x}" y="${-r - 5.6 + ((x - rx1) / bLen) * 2.5}" width="14" height="2.6" fill="#08090a"/>`);
        out.push(`<circle cx="${muzzle - 6}" cy="${-r - 9}" r="2.6" fill="#e9e9e9"/>`);
        // Wechselchokes / Mündung
        out.push(`<rect x="${muzzle - 3}" y="${-r + 1}" width="5" height="${4 * r - 6}" rx="1.5" fill="url(#steel)"/>`);
        out.push(rim(`M ${rx1},${-r - 8.5} L ${muzzle - 3},${-r - 5.6}`, 0.3, 0.8));
    } else {
        out.push(`<path d="M ${rx1 - 10},${-br0} L ${rx1 + 70},${-br0} C ${rx1 + 140},${-br1 - 1.4} ${muzzle - 100},${-br1} ${muzzle},${-br1}
          L ${muzzle},${br1} C ${muzzle - 100},${br1} ${rx1 + 140},${br1 + 1.4} ${rx1 + 70},${br0} L ${rx1 - 10},${br0} Z" fill="url(#${o.barrelFill ?? 'barrel'})"/>`);
        out.push(`<rect x="${muzzle - 1}" y="${-br1 - 0.5}" width="${o.muzzleCap ? 16 : 3}" height="${br1 * 2 + 1}" rx="1.5" fill="url(#steel)"/>`);
        if (o.fluted) for (let k = -1; k <= 1; k++) out.push(`<path d="M ${rx1 + 90},${k * br0 * 0.45} L ${muzzle - 80},${k * br1 * 0.45}" stroke="#000" stroke-opacity=".55" stroke-width="2.4" stroke-linecap="round"/>`);
        if (o.ironSights) {
            out.push(`<path d="M ${muzzle - 22},${-br1} L ${muzzle - 19},${-br1 - 10} L ${muzzle - 13},${-br1 - 10} L ${muzzle - 10},${-br1} Z" fill="url(#steel)"/>`);
            out.push(`<rect x="${muzzle - 17.5}" y="${-br1 - 11.5}" width="3" height="3" rx="1.5" fill="#cfa45a"/>`);
        }
    }

    // --- Schaft -------------------------------------------------------------
    let path;
    let hole = '';
    if (stock === 'shotgun') {
        path = `M 2,54 C ${rx0 - 200},40 ${rx0 - 150},34 ${rx0 - 120},30 C ${rx0 - 70},22 ${rx0 - 30},0 ${rx0 + 2},-8
          L ${rx0 + 8},40 C ${rx0 - 4},48 ${rx0 - 14},64 ${rx0 - 20},90 Q ${rx0 - 24},102 ${rx0 - 38},104 L ${rx0 - 58},104
          C ${rx0 - 80},104 ${rx0 - 110},112 ${rx0 - 150},124 L 12,182 Q 4,182 2,174 Z`;
        // Vorderschaft separat (umschließt den unteren Lauf)
        const fd = `M ${rx1 + 4},10 L ${fe - 14},11 Q ${fe},12 ${fe},24 Q ${fe - 2},36 ${fe - 20},38 L ${rx1 + 40},44 Q ${rx1 + 10},46 ${rx1 + 4},32 Z`;
        out.push(`<path d="${fd}" fill="${sf}"/>`);
        out.push(`<path d="${fd}" fill="#000" filter="url(#grainMM)" opacity=".7"/>`);
        out.push(`<path d="${fd}" fill="url(#stockShade)"/>`);
        out.push(`<path d="M ${rx1 + 60},16 L ${fe - 50},17 L ${fe - 56},34 L ${rx1 + 66},38 Z" fill="url(#checker)" opacity=".6"/>`);
        out.push(`<path d="${fd}" fill="none" stroke="#000" stroke-opacity=".55" stroke-width="1"/>`);
    } else {
        const comb = o.comb ?? 22;
        const toe = o.toe ?? 150;
        const forTop = o.forendTop ?? 9;
        const forBot = o.forendBottom ?? 50;
        const magBot = o.magBottom ?? 70;
        if (stock === 'thumbhole') {
            path = `M 2,${comb + 4} L ${rx0 - 110},${comb} C ${rx0 - 70},${comb - 2} ${rx0 - 40},${forTop} ${rx0},${forTop}
              L ${fe - 6},${forTop} Q ${fe},${forTop + 1} ${fe + 2},${forTop + 8} L ${fe},${forBot - 6} Q ${fe - 4},${forBot} ${fe - 16},${forBot}
              L ${rx0 + 210},${magBot - 8} L ${rx0 + 80},${magBot} L ${rx0 + 18},${magBot + 4}
              C ${rx0 - 2},${magBot + 10} ${rx0 - 14},${magBot + 40} ${rx0 - 10},${toe - 2} L ${rx0 - 42},${toe + 2}
              C ${rx0 - 48},${toe - 14} ${rx0 - 66},${toe - 22} ${rx0 - 96},${toe - 24}
              L 12,${toe} Q 4,${toe} 2,${toe - 8} Z`;
            hole = `M ${rx0 - 104},${comb + 26} C ${rx0 - 70},${comb + 18} ${rx0 - 40},${comb + 18} ${rx0 - 28},${comb + 26}
              C ${rx0 - 30},${comb + 52} ${rx0 - 34},${toe - 52} ${rx0 - 40},${toe - 40} C ${rx0 - 56},${toe - 46} ${rx0 - 80},${toe - 50} ${rx0 - 104},${toe - 52}
              C ${rx0 - 114},${comb + 60} ${rx0 - 114},${comb + 34} ${rx0 - 104},${comb + 26} Z`;
        } else {
            const grip = o.gripDrop ?? 132;
            path = `M 2,${comb + 6} L ${rx0 - 110},${comb} C ${rx0 - 60},${comb - 3} ${rx0 - 34},${forTop} ${rx0},${forTop}
              L ${fe - 6},${forTop} Q ${fe},${forTop + 1} ${fe + 2},${forTop + 8} L ${fe},${forBot - 6} Q ${fe - 4},${forBot} ${fe - 16},${forBot}
              L ${rx0 + 210},${magBot - 8} L ${rx0 + 80},${magBot} L ${rx0 + 20},${magBot + 2}
              C ${rx0 - 4},${magBot + 8} ${rx0 - 16},${grip - 30} ${rx0 - 26},${grip} L ${rx0 - 56},${grip + 2}
              C ${rx0 - 62},${grip - 20} ${rx0 - 74},${grip - 34} ${rx0 - 104},${grip - 36}
              L 12,${toe} Q 4,${toe} 2,${toe - 8} Z`;
        }
    }
    out.push(`<path d="${path} ${hole}" fill="${sf}" fill-rule="evenodd"/>`);
    if (isWood) {
        out.push(`<path d="${path} ${hole}" fill="#000" fill-rule="evenodd" filter="url(#grainMM)" opacity="${sf.includes('beech') ? 0.6 : 0.7}"/>`);
        if (!sf.includes('beech')) out.push(`<path d="${path} ${hole}" fill="#000" fill-rule="evenodd" filter="url(#figureMM)" opacity=".5"/>`);
    }
    out.push(`<path d="${path} ${hole}" fill="url(#stockShade)" fill-rule="evenodd"/>`);
    out.push(`<path d="${path} ${hole}" fill="none" stroke="#000" stroke-opacity=".55" stroke-width="1"/>`);
    if (hole) out.push(`<path d="${hole}" fill="none" stroke="#000" stroke-opacity=".7" stroke-width="3" transform="translate(1.5 2)"/>`);
    // Soft-Touch-Einlagen (Kunststoffschaft)
    if (o.inlays) {
        const fy = (o.forendTop ?? 9) + 10;
        out.push(`<path d="M ${rx1 + 20},${fy} L ${fe - 30},${fy} Q ${fe - 22},${fy + 8} ${fe - 30},${fy + 16} L ${rx1 + 26},${fy + 22} Q ${rx1 + 14},${fy + 12} ${rx1 + 20},${fy} Z" fill="url(#stipple)" opacity=".8"/>`);
        out.push(`<path d="M ${rx0 - 30},${(o.magBottom ?? 70) + 10} C ${rx0 - 34},${(o.magBottom ?? 70) + 34} ${rx0 - 34},${(o.toe ?? 150) - 30} ${rx0 - 36},${(o.toe ?? 150) - 14} L ${rx0 - 22},${(o.toe ?? 150) - 12} C ${rx0 - 20},${(o.magBottom ?? 70) + 40} ${rx0 - 18},${(o.magBottom ?? 70) + 20} ${rx0 - 14},${(o.magBottom ?? 70) + 12} Z" fill="url(#stipple)" opacity=".8"/>`);
    }
    if (o.checkering) {
        out.push(`<path d="M ${rx1 + 30},${(o.forendTop ?? 9) + 8} L ${rx1 + 150},${(o.forendTop ?? 9) + 8} L ${rx1 + 140},${(o.forBot ?? 34)} L ${rx1 + 40},${(o.forBot ?? 34) + 4} Z" fill="url(#checker)" opacity=".55"/>`);
    }
    // Schaftkappe
    const toeY = stock === 'shotgun' ? 176 : (o.toe ?? 150);
    const heelY = stock === 'shotgun' ? 48 : (o.comb ?? 22) + 6;
    out.push(`<path d="M -16,${heelY - 1} L 4,${heelY} L 14,${toeY} L -6,${toeY + 1} Q -14,${toeY} -14,${toeY - 8} L -18,${heelY + 6} Q -18,${heelY} -16,${heelY - 1} Z" fill="url(#tubeRubber)"/>`);
    out.push(`<line x1="3" y1="${heelY}" x2="13" y2="${toeY}" stroke="#000" stroke-opacity=".7" stroke-width="1.5"/>`);
    // Riemenbügel
    if (o.swivels !== false && stock !== 'shotgun') {
        out.push(`<path d="M 90,${(o.toe ?? 150) - 22} a 8 8 0 1 0 14 4" fill="none" stroke="url(#steel)" stroke-width="3"/>`);
        out.push(`<path d="M ${fe - 50},${(o.forendBottom ?? 50)} a 8 8 0 1 0 14 2" fill="none" stroke="url(#steel)" stroke-width="3"/>`);
    }
    // Schaft-Oberkante Licht
    out.push(rim(`M 4,${heelY + 1} L ${rx0 - 110},${stock === 'shotgun' ? 30.5 : (o.comb ?? 22) + 0.5}`, 0.2, 1.4));

    // --- System -----------------------------------------------------------
    const ry0 = o.receiverTop ?? -15;
    const ry1 = o.receiverBottom ?? 14;
    const recFill = o.receiverFill ?? 'url(#steel)';
    if (o.ou) {
        const rec = `M ${rx0 - 2},-12 L ${rx1 - 6},-12 Q ${rx1},-12 ${rx1},-6 L ${rx1},30 Q ${rx1 - 2},44 ${rx1 - 22},46 L ${rx0 + 30},46 Q ${rx0 + 10},46 ${rx0 + 6},38 L ${rx0 - 2},4 Z`;
        out.push(`<path d="${rec}" fill="${recFill}"/>`);
        out.push(`<path d="${rec}" fill="url(#stockShade)" opacity=".6"/>`);
        // Seitenplatte mit dezenter Kontur (ohne Gravur/Logo)
        out.push(`<path d="M ${rx0 + 16},-2 L ${rx1 - 20},-2 Q ${rx1 - 10},-2 ${rx1 - 10},8 L ${rx1 - 10},26 Q ${rx1 - 14},36 ${rx1 - 30},36 L ${rx0 + 34},36 Q ${rx0 + 18},34 ${rx0 + 14},20 Z" fill="none" stroke="#000" stroke-opacity=".28" stroke-width="1.2"/>`);
        out.push(`<path d="M ${rx0 + 16},-1 L ${rx1 - 20},-1" stroke="#fff" stroke-opacity=".45" stroke-width=".8"/>`);
        out.push(`<circle cx="${rx1 - 12}" cy="32" r="6" fill="url(#steel)"/><circle cx="${rx1 - 12}" cy="32" r="2" fill="#111"/>`);
        // Verschlusshebel oben + Sicherungsschieber
        out.push(`<path d="M ${rx0 + 10},-12 L ${rx0 - 26},-16 Q ${rx0 - 36},-15 ${rx0 - 32},-10 L ${rx0 + 6},-6 Z" fill="url(#blued)"/>`);
        out.push(rim(`M ${rx0},-11.4 L ${rx1 - 6},-11.4`, 0.6, 1.2));
    } else {
        out.push(`<path d="M ${rx0},${ry0 + 6} Q ${rx0},${ry0} ${rx0 + 8},${ry0} L ${rx1 - 4},${ry0} Q ${rx1},${ry0} ${rx1},${ry0 + 4} L ${rx1},${ry1} L ${rx0 + 6},${ry1} Q ${rx0},${ry1} ${rx0},${ry1 - 4} Z" fill="${recFill}"/>`);
        out.push(rim(`M ${rx0 + 8},${ry0 + 0.8} L ${rx1 - 4},${ry0 + 0.8}`, 0.4, 1));
        // Auswurffenster
        out.push(`<rect x="${rx0 + rLen * 0.42}" y="${ry0 + 5}" width="${rLen * 0.3}" height="${(ry1 - ry0) * 0.5}" rx="3" fill="#0b0c0d"/>`);
        out.push(`<rect x="${rx0 + rLen * 0.44}" y="${ry0 + 8}" width="${rLen * 0.26}" height="${(ry1 - ry0) * 0.3}" rx="2" fill="url(#stainless)" opacity=".85"/>`);
    }
    // Kammerstängel
    const bx = o.boltX ?? rx0 + 34;
    if (o.action === 'straight') {
        // Geradezug: Griff hinten am Verschluss, Kugel nach hinten unten
        out.push(`<path d="M ${bx + 10},${ry0 + 10} L ${bx - 10},${ry0 + 30} " stroke="url(#steel)" stroke-width="9" stroke-linecap="round"/>`);
        out.push(`<circle cx="${bx - 14}" cy="${ry0 + 36}" r="12" fill="url(#ballGrad)"/>`);
        // Handspannschieber oben
        out.push(`<path d="M ${rx0 - 24},${ry0 + 6} L ${rx0 + 10},${ry0 + 2} L ${rx0 + 14},${ry0 + 10} L ${rx0 - 24},${ry0 + 14} Q ${rx0 - 30},${ry0 + 10} ${rx0 - 24},${ry0 + 6} Z" fill="url(#anod)"/>`);
        out.push(lines(rx0 - 18, rx0 + 4, 4, ry0 + 4, ry0 + 13, 0, '#000', 0.6, 1.4, false));
    } else if (o.action === 'turn' || o.action === 'rimfire') {
        const ang = o.action === 'rimfire' ? 22 : 36;
        out.push(`<circle cx="${bx}" cy="${0}" r="11" fill="url(#steel)"/>`);
        out.push(`<path d="M ${bx},0 L ${bx - ang},${44}" stroke="url(#steelH)" stroke-width="7" stroke-linecap="round"/>`);
        out.push(`<circle cx="${bx - ang - 3}" cy="${50}" r="${o.action === 'rimfire' ? 9 : 11}" fill="url(#${o.boltKnob ?? 'ballGrad'})"/>`);
        // Schlagbolzenmutter / Sicherung
        out.push(`<path d="M ${rx0 - 2},${ry0 + 6} L ${rx0 - 26},${ry0 + 9} Q ${rx0 - 32},${ry0 + 14} ${rx0 - 26},${ry1 - 6} L ${rx0 - 2},${ry1 - 4} Z" fill="url(#steel)"/>`);
        out.push(`<rect x="${rx0 - 20}" y="${ry0 + 2}" width="10" height="7" rx="2" fill="url(#anod)"/>`);
    }
    // Abzugsbügel + Abzug
    const tg = o.triggerX ?? rx0 + 70;
    const tgY = stock === 'shotgun' ? 40 : (o.magBottom ?? 70) - 4;
    out.push(`<path d="M ${tg - 38},${tgY - 8} C ${tg - 50},${tgY + 30} ${tg + 34},${tgY + 38} ${tg + 44},${tgY - 8} L ${tg + 37},${tgY - 8} C ${tg + 28},${tgY + 27} ${tg - 40},${tgY + 21} ${tg - 31},${tgY - 8} Z" fill="url(#steelH)"/>`);
    out.push(`<path d="M ${tg - 2},${tgY - 10} C ${tg - 10},${tgY} ${tg - 12},${tgY + 10} ${tg - 8},${tgY + 18}" fill="none" stroke="url(#steel)" stroke-width="4.5" stroke-linecap="round"/>`);
    // Magazin
    if (o.mag) out.push(`<path d="M ${tg + 52},${tgY - 6} L ${tg + 120},${tgY - 8} L ${tg + 118},${tgY + (o.mag === 'long' ? 24 : 8)} Q ${tg + 118},${tgY + (o.mag === 'long' ? 28 : 12)} ${tg + 112},${tgY + (o.mag === 'long' ? 28 : 12)} L ${tg + 58},${tgY + (o.mag === 'long' ? 28 : 12)} Q ${tg + 52},${tgY + (o.mag === 'long' ? 28 : 12)} ${tg + 52},${tgY + 4} Z" fill="url(#polymer)"/>`);

    // --- Montage + Zielfernrohr --------------------------------------------
    if (o.scope) {
        const sc = o.scope;
        const sy = sc.y ?? -58;
        const sx = sc.x ?? rx0 - 40;
        for (const m of sc.mounts ?? [70, 200]) {
            const mx = sx + m;
            out.push(`<path d="M ${mx - 12},${ry0 + 1} L ${mx + 12},${ry0 + 1} L ${mx + 9},${sy + 14} L ${mx - 9},${sy + 14} Z" fill="url(#anod)"/>`);
            out.push(`<rect x="${mx - 13}" y="${sy - 19}" width="26" height="38" rx="5" fill="url(#anod)"/>`);
            out.push(`<circle cx="${mx}" cy="${sy + 22}" r="3" fill="#3a3c40" stroke="#000" stroke-width=".6"/>`);
        }
        out.push(`<g transform="translate(${sx} ${sy})">${scope(sc)}</g>`);
        for (const m of sc.mounts ?? [70, 200]) {
            const mx = sx + m;
            out.push(`<rect x="${mx - 13}" y="${sy - 18}" width="26" height="36" rx="5" fill="url(#anod)" opacity=".96"/>`);
            out.push(`<line x1="${mx - 13}" y1="${sy}" x2="${mx + 13}" y2="${sy}" stroke="#000" stroke-opacity=".7" stroke-width="1.2"/>`);
            out.push(`<circle cx="${mx - 7}" cy="${sy - 9}" r="2" fill="#555"/><circle cx="${mx + 7}" cy="${sy + 9}" r="2" fill="#555"/>`);
            out.push(rim(`M ${mx - 11},${sy - 17.3} L ${mx + 11},${sy - 17.3}`, 0.3, 0.8));
        }
    }
    return `<defs>
  <linearGradient id="stockShade" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#fff" stop-opacity=".22"/><stop offset=".06" stop-color="#fff" stop-opacity=".08"/><stop offset=".25" stop-color="#fff" stop-opacity="0"/>
    <stop offset=".55" stop-color="#000" stop-opacity=".05"/><stop offset="1" stop-color="#000" stop-opacity=".55"/></linearGradient>
  <linearGradient id="steelH" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8c9096"/><stop offset=".5" stop-color="#2c2e32"/><stop offset="1" stop-color="#0e0f10"/></linearGradient>
  <radialGradient id="ballGrad" cx="35%" cy="30%" r="70%"><stop offset="0" stop-color="#d9dcdf"/><stop offset=".3" stop-color="#6e7277"/><stop offset=".8" stop-color="#1c1d20"/><stop offset="1" stop-color="#0b0b0c"/></radialGradient>
  <radialGradient id="ballWood" cx="35%" cy="30%" r="70%"><stop offset="0" stop-color="#d9a26c"/><stop offset=".4" stop-color="#7a4a24"/><stop offset="1" stop-color="#2a150a"/></radialGradient>
  <filter id="grainMM" x="0" y="0" width="100%" height="100%">
    <feTurbulence type="fractalNoise" baseFrequency=".008 .32" numOctaves="3" seed="${o.seed ?? 5}"/>
    <feColorMatrix values="0 0 0 0 .12  0 0 0 0 .05  0 0 0 0 .01  0 0 0 -2.2 1.25"/>
    <feComposite in2="SourceGraphic" operator="in"/>
  </filter>
  <filter id="figureMM" x="0" y="0" width="100%" height="100%">
    <feTurbulence type="turbulence" baseFrequency=".012 .05" numOctaves="2" seed="${(o.seed ?? 5) + 3}"/>
    <feColorMatrix values="0 0 0 0 .05  0 0 0 0 .02  0 0 0 0 0  0 0 0 -2 .9"/>
    <feComposite in2="SourceGraphic" operator="in"/>
  </filter>
  <linearGradient id="stockSpec" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".3" stop-color="#fff" stop-opacity=".22"/><stop offset=".7" stop-color="#fff" stop-opacity=".05"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>
  <pattern id="checker" width="3" height="3" patternUnits="userSpaceOnUse" patternTransform="rotate(40)"><rect width="3" height="3" fill="#000" fill-opacity=".25"/><path d="M0 0 L3 3 M3 0 L0 3" stroke="#000" stroke-opacity=".6" stroke-width=".5"/></pattern>
</defs>` + out.join('\n');
}
