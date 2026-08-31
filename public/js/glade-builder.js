const map = document.querySelector('[data-editable]');
const hidden = document.querySelector('#mapDefinition');
const selectedLabel = document.querySelector('#selectedCode');
let selected = 'C3';
let painting = false;

function classesFor(code) {
    const kind = code[0].toLowerCase();
    const color = kind === 'c' ? code[1] : ['d', 'e'].includes(kind) ? 4 : kind === 'r' ? 2 : ['s', 'b'].includes(kind) ? 0 : 3;
    return `tile tile-${kind} color-${color}`;
}

function updateHidden() {
    const codes = [...map.querySelectorAll('.tile')].map(tile => tile.dataset.code);
    hidden.value = codes.join(' ');
    const starts = codes.filter(code => code.startsWith('S')).length;
    const goals = codes.filter(code => code.startsWith('D')).length;
    document.querySelector('#tileCounts').textContent = `${starts} start · ${goals} doel${goals === 1 ? '' : 'en'}`;
}

function paint(tile) {
    if (!tile?.classList.contains('tile')) return;
    if (selected.startsWith('S')) {
        map.querySelectorAll('[data-code^="S"]').forEach(oldStart => {
            oldStart.dataset.code = 'C3';
            oldStart.className = classesFor('C3');
            oldStart.querySelector('span').textContent = '3';
        });
    }
    tile.dataset.code = selected;
    tile.className = classesFor(selected);
    tile.querySelector('span').textContent = selected.slice(1);
    updateHidden();
}

document.querySelectorAll('[data-tile]').forEach(button => button.addEventListener('click', () => {
    selected = button.dataset.tile;
    selectedLabel.textContent = selected;
    document.querySelectorAll('[data-tile]').forEach(item => item.classList.toggle('selected', item === button));
}));

map.addEventListener('pointerdown', event => { painting = true; paint(event.target.closest('.tile')); });
map.addEventListener('pointerover', event => { if (painting) paint(event.target.closest('.tile')); });
document.addEventListener('pointerup', () => painting = false);
document.querySelector('#fillField').addEventListener('click', () => {
    map.querySelectorAll('.tile').forEach(tile => {
        tile.dataset.code = 'C3'; tile.className = classesFor('C3'); tile.querySelector('span').textContent = '3';
    });
    updateHidden();
});

updateHidden();
