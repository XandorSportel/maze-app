const code = document.querySelector('#code');
const numbers = document.querySelector('#lineNumbers');
const count = document.querySelector('#lineCount');

function updateLines() {
    const total = code.value.split('\n').length;
    numbers.textContent = Array.from({ length: total }, (_, index) => index + 1).join('\n');
    count.textContent = `${total} ${total === 1 ? 'regel' : 'regels'}`;
}

code.addEventListener('input', updateLines);
code.addEventListener('scroll', () => numbers.scrollTop = code.scrollTop);
updateLines();
