document.addEventListener('DOMContentLoaded', function(){
    const bracket = window.bracketData || null;
    const container = document.getElementById('bracketContainer');
    const svg = document.getElementById('bracketSvg');

    if (!container || !svg) return;

    function clearSvg(){
        while (svg.firstChild) svg.removeChild(svg.firstChild);
    }

    function setSvgSize(w,h){
        svg.setAttribute('width', w);
        svg.setAttribute('height', h);
        svg.style.width = '100%';
        svg.style.height = h + 'px';
    }

    function createRect(x,y,w,h,rx,ry,cls){
        const rect = document.createElementNS('http://www.w3.org/2000/svg','rect');
        rect.setAttribute('x', x);
        rect.setAttribute('y', y);
        rect.setAttribute('width', w);
        rect.setAttribute('height', h);
        rect.setAttribute('rx', rx||4);
        rect.setAttribute('ry', ry||4);
        rect.setAttribute('class', cls || 'bracket-box');
        return rect;
    }

    function createText(x,y,txt,cls){
        const t = document.createElementNS('http://www.w3.org/2000/svg','text');
        t.setAttribute('x', x);
        t.setAttribute('y', y);
        t.setAttribute('class', cls || 'bracket-text');
        t.setAttribute('dominant-baseline','middle');
        t.textContent = txt;
        return t;
    }

    function createLine(x1,y1,x2,y2,cls){
        const l = document.createElementNS('http://www.w3.org/2000/svg','line');
        l.setAttribute('x1', x1);
        l.setAttribute('y1', y1);
        l.setAttribute('x2', x2);
        l.setAttribute('y2', y2);
        l.setAttribute('class', cls || 'bracket-line');
        return l;
    }

    function renderSingleElimination(rounds){
        if (!rounds || rounds.length === 0) return;
        const colW = 220;
        const boxH = 36;
        const vGap = 18;
        const cols = rounds.length;
        const maxMatches = Math.max(...rounds.map(r => r.matches.length));
        const svgW = cols * colW + 40;
        const svgH = Math.max(200, maxMatches * (boxH + vGap) * Math.pow(2,0));

        clearSvg();
        setSvgSize(svgW, svgH + 20);

        const positions = [];

        rounds.forEach((round, ri) => {
            const x = 20 + ri * colW;
            const matches = round.matches;
            const totalH = matches.length * (boxH + vGap);
            const startY = (svgH - totalH) / 2 + 10;
            positions[ri] = [];

            matches.forEach((m, mi) => {
                const y = startY + mi * (boxH + vGap);
                const gX = x;
                const gY = y;

                // box
                const rect = createRect(gX, gY, 180, boxH, 6,6,'bracket-box');
                svg.appendChild(rect);

                // left team text
                const tA = createText(gX + 10, gY + boxH/2, m.a ? m.a.name : 'TBD', 'bracket-text team-a');
                svg.appendChild(tA);

                // vs or bye
                const tB = createText(gX + 10, gY + boxH/2 + 12, m.b ? ('vs ' + m.b.name) : '(Bye)', 'bracket-subtext');
                svg.appendChild(tB);

                positions[ri][mi] = {x: gX + 180, y: gY + boxH/2};
            });
        });

        // draw connections
        for (let ri = 0; ri < positions.length - 1; ri++){
            const cur = positions[ri];
            const next = positions[ri+1];
            if (!cur || !next) continue;
            cur.forEach((p, idx) => {
                const targetIdx = Math.floor(idx/2);
                const p2 = next[targetIdx];
                if (!p2) return;
                const line = createLine(p.x, p.y, p2.x - 10, p2.y, 'bracket-line');
                svg.appendChild(line);
            });
        }
    }

    function renderRoundRobin(rounds){
        clearSvg();
        // create textual list in svg for accessibility
        const left = 20;
        const lineH = 20;
        const svgW = container.clientWidth - 40;
        const svgH = Math.max(200, rounds.length * rounds.reduce((m, r) => Math.max(m, r.matches.length),0) * lineH);
        setSvgSize(svgW, svgH + 20);
        let y = 20;
        rounds.forEach((r) => {
            const title = createText(left, y + 10, 'Manche ' + r.round, 'bracket-title');
            svg.appendChild(title);
            y += 24;
            r.matches.forEach((m) => {
                const txt = (m.a ? m.a.name : 'TBD') + (m.b && m.b.name ? ' vs ' + m.b.name : ' (BYE)');
                const t = createText(left + 8, y + 10, txt, 'bracket-text');
                svg.appendChild(t);
                y += lineH;
            });
            y += 10;
        });
    }

    function render(br){
        if (!br) return;
        if (br.type === 'single_elimination') {
            renderSingleElimination(br.rounds);
        } else if (br.type === 'round_robin') {
            renderRoundRobin(br.rounds);
        } else if (br.type === 'double_elimination') {
            // render winners bracket
            if (br.winners) renderSingleElimination(br.winners.rounds);
        }
    }

    // basic styles appended to head for bracket
    (function addStyles(){
        const css = `
            .bracket-box{ fill:#fff; stroke:#e6e6e6; stroke-width:1; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.04)); }
            .bracket-text{ font-family: Inter, system-ui, Arial; font-size:12px; fill:#222; }
            .bracket-subtext{ font-family: Inter, system-ui, Arial; font-size:11px; fill:#666; }
            .bracket-line{ stroke:#cfd8e3; stroke-width:1.5; }
            .bracket-title{ font-family: Inter, system-ui, Arial; font-weight:600; font-size:14px; fill:#0b3d91; }
        `;
        const s = document.createElement('style');
        s.type = 'text/css';
        s.appendChild(document.createTextNode(css));
        document.head.appendChild(s);
    })();

    render(bracket);
});
