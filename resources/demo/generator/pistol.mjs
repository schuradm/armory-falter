import { lines, rim } from './common.mjs';

/**
 * Pistole in Seitenansicht (linke Seite, Mündung links), Maße in mm.
 * Ursprung: Mündung/Oberkante Verschluss. Rückgabe: SVG-Gruppe in mm-Koordinaten.
 */
export function pistol(o) {
    const L = o.slideLen ?? 186; // Schlittenlänge
    const S = o.slideH ?? 28; // Schlittenhöhe
    const R = L; // Schlittenende
    const ga = o.gripSlant ?? 30; // Versatz Griffunterkante nach hinten
    const gh = o.gripH ?? 100; // Griffhöhe unter Schlitten
    const slideFill = o.slideFill ?? 'url(#steel)';
    const frameFill = o.frameFill ?? 'url(#polymer)';
    const dcFront = o.dustFront ?? 12; // Griffstückfront
    const guardFront = R - (o.guardOffset ?? 116);
    const gTop = S + 15;
    const out = [`<defs>
  <linearGradient id="castShadow" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#000" stop-opacity=".6"/><stop offset="1" stop-color="#000" stop-opacity="0"/></linearGradient>
  <linearGradient id="roundG" x1="0" y1="0" x2="1" y2="0">
    <stop offset="0" stop-color="#000" stop-opacity=".45"/><stop offset=".18" stop-color="#fff" stop-opacity=".07"/>
    <stop offset=".45" stop-color="#fff" stop-opacity=".02"/><stop offset=".85" stop-color="#000" stop-opacity=".15"/><stop offset="1" stop-color="#000" stop-opacity=".5"/></linearGradient>
  <pattern id="stippleMM" width="1.1" height="1.1" patternUnits="userSpaceOnUse" patternTransform="rotate(30)">
    <rect width="1.1" height="1.1" fill="#1d1e20"/><circle cx=".3" cy=".3" r=".24" fill="#36383c"/><circle cx=".85" cy=".85" r=".22" fill="#0c0d0e"/></pattern>
  <pattern id="pyramidMM" width="1.4" height="1.4" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
    <rect width="1.4" height="1.4" fill="#1a1b1d"/><path d="M0 0 L.7 .7 L1.4 0Z" fill="#3a3c40"/><path d="M0 1.4 L.7 .7 L1.4 1.4Z" fill="#0a0a0b"/></pattern>
  <pattern id="ribMM" width="1.6" height="1.6" patternUnits="userSpaceOnUse" patternTransform="rotate(-12)">
    <rect width="1.6" height="1.6" fill="#1c1d1f"/><rect width="1.6" height=".5" fill="#34363a"/></pattern>
</defs>`];

    // --- Griffstück -------------------------------------------------------
    const gw = o.gripW ?? 52; // Griffbreite oben (Bügelende bis Rücken)
    const bs = o.backSlant ?? 22;
    const gf0 = [R - gw, S + 15]; // Griff vorne oben
    const gf1 = [R - gw + ga, S + gh]; // Griff vorne unten
    const gb1 = [R + 2 + bs, S + gh]; // Griff hinten unten
    const gb0 = [R + 3, S + 14];
    const bt = o.beaver ?? 10; // Biberschwanz
    let frame = `M ${dcFront},${S - 2}
      L ${R + 2},${S - 2}
      Q ${R + bt},${S - 1} ${R + bt + 1},${S + 6}
      Q ${R + bt - 2},${S + 10} ${gb0[0]},${gb0[1]}
      C ${gb0[0] + 4},${S + 40} ${gb1[0] - 2},${S + gh - 30} ${gb1[0]},${gb1[1] - 4}
      L ${gb1[0]},${gb1[1]}
      L ${gf1[0]},${gf1[1]}
      ${o.fingerGrooves ? `C ${gf1[0] - 9},${S + gh - 20} ${gf1[0] - 4},${S + gh - 34} ${gf1[0] - 10},${S + 55} C ${gf1[0] - 16},${S + 44} ${gf0[0]},${S + 36} ${gf0[0]},${gf0[1] + 6}` : `C ${gf1[0] - 6},${S + gh - 30} ${gf0[0] + 2},${S + 34} ${gf0[0]},${gf0[1] + 4}`}
      L ${gf0[0]},${gf0[1]}`;
    // Abzugsbügel
    if (o.guard === 'round') {
        frame += ` Q ${gf0[0] - 6},${S + 44} ${gf0[0] - 26},${S + 45}
          C ${guardFront + 4},${S + 45} ${guardFront - 2},${S + 34} ${guardFront},${S + 20}
          L ${guardFront - 4},${S + 15}`;
    } else {
        frame += ` L ${gf0[0] - 4},${S + 42}
          L ${guardFront + 6},${S + 42}
          Q ${guardFront},${S + 42} ${guardFront},${S + 36}
          L ${guardFront + 1},${S + 15}`;
    }
    frame += ` L ${dcFront + 3},${S + 15} Q ${dcFront},${S + 15} ${dcFront},${S + 11} Z`;
    // Abzugsbügel-Öffnung
    const gi = o.guard === 'round'
        ? `M ${gf0[0] - 6},${S + 17} L ${guardFront + 7},${S + 17} C ${guardFront + 5},${S + 30} ${guardFront + 10},${S + 38} ${gf0[0] - 26},${S + 38} Q ${gf0[0] - 10},${S + 37} ${gf0[0] - 6},${S + 17} Z`
        : `M ${gf0[0] - 6},${S + 17} L ${guardFront + 8},${S + 17} L ${guardFront + 7},${S + 33} Q ${guardFront + 7},${S + 36} ${guardFront + 10},${S + 36} L ${gf0[0] - 9},${S + 36} Z`;

    out.push(`<path d="${frame} ${gi}" fill="${frameFill}" fill-rule="evenodd"/>`);
    out.push(`<path d="${frame} ${gi}" fill="url(#sheenL)" fill-rule="evenodd" opacity=".35"/>`);
    // Innenkante des Bügels (Materialstärke)
    out.push(`<path d="${gi}" fill="none" stroke="#000" stroke-opacity=".55" stroke-width="1.6" transform="translate(.5 .6)"/>`);
    // Griffstück-Kante oben, Lichtkante Bügel
    out.push(rim(`M ${dcFront + 2},${S + 15.3} L ${guardFront},${S + 15.3}`, 0.12, 0.6));
    out.push(`<path d="${gi}" fill="none" stroke="#fff" stroke-opacity=".1" stroke-width=".6"/>`);

    // Schlagschatten des Schlittens auf dem Griffstück
    out.push(`<rect x="${dcFront}" y="${S - 1}" width="${R + 6 - dcFront}" height="6" fill="url(#castShadow)"/>`);
    // Griff-Textur (Panel)
    const tx = 4;
    const panel = `M ${gf0[0] + tx},${S + 22} L ${gb0[0] - 3},${S + 22} C ${gb0[0] + 1},${S + 40} ${gb1[0] - 5},${S + gh - 30} ${gb1[0] - 4},${S + gh - 9}
      L ${gf1[0] + 3},${S + gh - 9} C ${gf1[0] - 3},${S + gh - 30} ${gf0[0] + tx + 2},${S + 34} ${gf0[0] + tx},${S + 22} Z`;
    out.push(`<path d="${panel}" fill="url(#${o.texture ?? 'stippleMM'})" opacity="${o.textureOpacity ?? 0.8}"/>`);
    const gripShape = `M ${gf0[0]},${S + 15} L ${gb0[0]},${S + 15} C ${gb0[0] + 4},${S + 40} ${gb1[0] - 2},${S + gh - 30} ${gb1[0]},${S + gh} L ${gf1[0]},${S + gh} C ${gf1[0] - 6},${S + gh - 30} ${gf0[0] + 2},${S + 34} ${gf0[0]},${S + 15} Z`;
    out.push(`<path d="${gripShape}" fill="url(#roundG)"/>`);
    if (o.gripPanel) {
        // Griffschalen (z. B. Stahlpistole) mit Schrauben
        out.push(`<path d="${panel}" fill="${o.gripPanel}" opacity=".9"/>`);
        out.push(`<path d="${panel}" fill="url(#pyramidMM)" opacity=".55"/>`);
        out.push(`<circle cx="${gf0[0] + 18}" cy="${S + 30}" r="2.4" fill="url(#stainless)"/><circle cx="${gf1[0] + 14}" cy="${S + gh - 18}" r="2.4" fill="url(#stainless)"/>`);
    }
    // Griffrücken-Fuge
    out.push(`<path d="M ${gb0[0] - 6},${S + 18} C ${gb0[0] - 2},${S + 40} ${gb1[0] - 8},${S + gh - 30} ${gb1[0] - 7},${S + gh - 2}" fill="none" stroke="#000" stroke-opacity=".5" stroke-width=".7"/>`);

    // Magazinboden
    const mb = o.magwell
        ? `M ${gf1[0] - 5},${S + gh - 6} L ${gb1[0] + 4},${S + gh - 6} L ${gb1[0] + 5},${S + gh + 8} Q ${gb1[0] + 5},${S + gh + 11} ${gb1[0] + 1},${S + gh + 11} L ${gf1[0] - 1},${S + gh + 11} Q ${gf1[0] - 6},${S + gh + 11} ${gf1[0] - 6},${S + gh + 6} Z`
        : `M ${gf1[0] - 1},${S + gh} L ${gb1[0] + 2},${S + gh} L ${gb1[0] + 2},${S + gh + 7} Q ${gb1[0] + 2},${S + gh + 9} ${gb1[0]},${S + gh + 9} L ${gf1[0] + 2},${S + gh + 9} Q ${gf1[0] - 2},${S + gh + 9} ${gf1[0] - 2},${S + gh + 5} Z`;
    out.push(`<path d="${mb}" fill="${o.magwell ? 'url(#anod)' : 'url(#polymerG)'}"/>`);
    out.push(rim(`M ${gf1[0]},${S + gh + (o.magwell ? -5.5 : 0.5)} L ${gb1[0] + 1},${S + gh + (o.magwell ? -5.5 : 0.5)}`, 0.18, 0.6));

    // Schiene unter dem Griffstück
    const railN = o.railSlots ?? 1;
    for (let i = 0; i < railN; i++) {
        const x = dcFront + 9 + i * 9.5;
        out.push(`<rect x="${x}" y="${S + 13}" width="4.5" height="2.2" fill="#0a0a0b"/>`);
    }

    // Abzug
    const tg = guardFront + 8 + 0.58 * (gf0[0] - 14 - guardFront);
    const trig = `M ${tg + 4},${S + 15.5} C ${tg - 2},${S + 19} ${tg - 4.5},${S + 26} ${tg - 3},${S + 33.5} L ${tg + 0.6},${S + 33.5} C ${tg - 0.8},${S + 27} ${tg + 1.5},${S + 21} ${tg + 8.5},${S + 16.5} Z`;
    out.push(`<path d="${trig}" fill="${o.triggerFill ?? 'url(#polymer)'}" stroke="#000" stroke-opacity=".6" stroke-width=".4"/>`);
    out.push(rim(`M ${tg + 3.4},${S + 16.6} C ${tg - 1.6},${S + 20} ${tg - 3.6},${S + 26} ${tg - 2.6},${S + 33}`, 0.3, 0.5));
    if (o.triggerSafety !== false) out.push(`<path d="M ${tg - 0.5},${S + 21} Q ${tg - 2.4},${S + 25} ${tg - 1.8},${S + 30}" stroke="#000" stroke-width="1.3" fill="none" opacity=".75"/>`);

    // --- Hahn (Stahlpistole) -----------------------------------------------
    if (o.hammer) {
        out.push(`<path d="M ${R - 2},${S - 2} L ${R + 3},${-6} Q ${R + 8},${-9} ${R + 11},${-3} L ${R + 9},${S - 4} Z" fill="url(#steel)"/>`);
        out.push(lines(R + 3.5, R + 9.5, 1.6, -5, 1, 0.6, '#000', 0.5, 0.5, false));
    }

    // --- Verschluss ------------------------------------------------------
    const front = o.slideNose === 'taper'
        ? `M 0,${S - 4} L 0,9 Q 1,3 7,1.4 L 14,0`
        : `M 0,${S - 4} L 0,6 Q 0.8,1 6,0`;
    const rear = o.slideRear === 'angled'
        ? `L ${R - 6},0 Q ${R - 2},0 ${R - 1},3 L ${R + 1},${S}`
        : `L ${R - 3},0 Q ${R},0 ${R},3 L ${R},${S}`;
    const slide = `${front} ${rear} L 3,${S} Q 0,${S} 0,${S - 4} Z`;
    // Läufe, die vorn überstehen (Bull-Barrel / Laufbuchse)
    if (o.barrelOut) {
        out.push(`<rect x="${-o.barrelOut}" y="${S * 0.22}" width="${o.barrelOut + 2}" height="${S * 0.56}" rx="1" fill="url(#barrel)"/>`);
        out.push(`<rect x="${-o.barrelOut - 0.6}" y="${S * 0.34}" width="1.4" height="${S * 0.32}" fill="#050505"/>`);
    }
    out.push(`<path d="${slide}" fill="${slideFill}"/>`);
    out.push(`<path d="${slide}" fill="url(#sheenL)"/>`);
    // Stahlrahmen, der den Schlitten seitlich umgreift (CZ-Bauweise)
    if (o.frameRails) {
        out.push(`<path d="M ${dcFront - 2},${S * 0.55} L ${R + 2},${S * 0.55} L ${R + 2},${S + 1} L ${dcFront - 2},${S + 1} Z" fill="${frameFill}"/>`);
        out.push(rim(`M ${dcFront - 1},${S * 0.55 + 0.4} L ${R + 1},${S * 0.55 + 0.4}`, 0.3, 0.6));
    }
    out.push(rim(`M 5,0.5 L ${R - 4},0.5`, 0.42, 0.9));
    out.push(rim(`M 4,${S * 0.18} L ${R - 2},${S * 0.18}`, 0.08, 1.6));
    out.push(`<path d="M 1,${S - 0.6} L ${R},${S - 0.6}" stroke="#000" stroke-opacity=".6" stroke-width="1"/>`);

    // Riffelungen
    const sr = o.serrations ?? 'vertical';
    const serrH0 = 2.5;
    const serrH1 = (o.frameRails ? S * 0.55 : S) - 2.5;
    const rearSerr = (x0, x1) => {
        if (sr === 'terrain') {
            let s = '';
            for (let x = x0; x <= x1; x += 4.6) {
                s += `<path d="M ${x},${serrH0} L ${x + 2.4},${serrH0} L ${x + 1.2},${serrH1} L ${x - 1.2},${serrH1} Z" fill="#000" opacity=".7"/>`;
                s += `<path d="M ${x + 2.4},${serrH0} L ${x + 1.2},${serrH1}" stroke="#fff" stroke-opacity=".16" stroke-width=".5"/>`;
            }
            return s;
        }
        if (sr === 'hk') {
            return lines(x0, x1, 3, serrH0, serrH1, 0, '#000', 0.65, 1.5)
                + `<path d="M ${x0 - 2},${S * 0.35} L ${x1 + 3},${S * 0.35} L ${x1 + 3},${S * 0.7} L ${x0 - 2},${S * 0.7} Z" fill="${slideFill}" opacity=".9"/>`
                + rim(`M ${x0 - 2},${S * 0.35} L ${x1 + 3},${S * 0.35}`, 0.3, 0.5);
        }
        return lines(x0, x1, o.serrStep ?? 3.2, serrH0, serrH1, o.serrSlant ?? 0, '#000', 0.65, 1.6);
    };
    out.push(rearSerr(R - (o.rearSerrLen ?? 32), R - 6));
    if (o.frontSerr !== false) out.push(rearSerr(16, 16 + (o.frontSerrLen ?? 20)));
    if (o.cuts) {
        // Gewichtsreduzierende Fenster oben im Schlitten
        for (let i = 0; i < o.cuts; i++) {
            const x = 42 + i * 17;
            out.push(`<path d="M ${x},2.4 L ${x + 11},2.4 L ${x + 9},9.5 L ${x - 2},9.5 Z" fill="#0b0b0c"/><path d="M ${x},2.4 L ${x + 11},2.4" stroke="#fff" stroke-opacity=".25" stroke-width=".5"/>`);
        }
    }
    // Auszieher / Schlittenende-Kante
    out.push(`<path d="M ${R - 1.4},2 L ${R - 1.4},${S - 2}" stroke="#000" stroke-opacity=".5" stroke-width="1.2"/>`);

    // Optic-Ready-Abdeckplatte
    if (o.optic) {
        const ox = R - 66;
        out.push(`<path d="M ${ox},0.2 L ${ox + 36},0.2 L ${ox + 36},${-2.2} Q ${ox + 36},-3 ${ox + 35},-3 L ${ox + 1},-3 Q ${ox},-3 ${ox},-2.2 Z" fill="url(#anod)"/>`);
        out.push(`<circle cx="${ox + 5}" cy="-1.4" r=".9" fill="#888"/><circle cx="${ox + 31}" cy="-1.4" r=".9" fill="#888"/>`);
    }

    // Visierung
    const fs = o.frontSightX ?? 9;
    out.push(`<path d="M ${fs},0.5 L ${fs + 1},-4.5 L ${fs + 5},-4.5 L ${fs + 6.5},0.5 Z" fill="url(#steel)"/>`);
    if (o.fiber) out.push(`<rect x="${fs + 1.4}" y="-4.2" width="3.6" height="1.6" rx=".8" fill="${o.fiber}"/>`);
    const rs = R - (o.rearSightOffset ?? 15);
    out.push(`<path d="M ${rs},0.5 L ${rs + 1.5},-5.5 L ${rs + 10},-5.5 L ${rs + 11},0.5 Z" fill="url(#steel)"/>`);
    out.push(`<rect x="${rs + 3}" y="-5" width="2" height="1.6" fill="#ddd" opacity=".8"/>`);

    // Bedienelemente: Schlittenfang, Zerlegehebel, Magazinhalter
    const ss = o.slideStopX ?? R - 74;
    out.push(`<path d="M ${ss},${S + 0.5} L ${ss + 18},${S + 0.5} Q ${ss + 21},${S + 1} ${ss + 21},${S + 3.5} L ${ss + 19},${S + 5.5} L ${ss + 2},${S + 4.2} Z" fill="url(#steel)"/>`);
    out.push(rim(`M ${ss + 1},${S + 0.9} L ${ss + 18},${S + 0.9}`, 0.35, 0.5));
    if (o.takedown !== false) out.push(`<rect x="${R - 117}" y="${S + 1}" width="8" height="5.2" rx="1.4" fill="url(#steel)"/>`);
    if (o.decocker) out.push(`<path d="M ${R - 8},${S + 1} L ${R + 1},${S + 1} L ${R + 2},${S + 8} L ${R - 6},${S + 7} Z" fill="url(#steel)"/>`);
    out.push(`<rect x="${gf0[0] - 3.5}" y="${S + 18}" width="6" height="9" rx="1.6" fill="${o.magRelFill ?? 'url(#polymerG)'}" stroke="#000" stroke-opacity=".6" stroke-width=".5"/>`);
    // Achsstifte
    for (const [x, y] of o.pins ?? [[R - 100, S + 9], [R - 66, S + 10], [R - 18, S + 8]]) {
        out.push(`<circle cx="${x}" cy="${y}" r="1.3" fill="#3a3c40" stroke="#000" stroke-opacity=".6" stroke-width=".4"/>`);
    }
    return out.join('\n');
}
