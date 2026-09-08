const fs = require('fs');
const content = fs.readFileSync('c:/xampp/htdocs/keria/index.html', 'utf8');

const s1 = "phan thị nhật an";
const s2 = "Phan Thị Nhật An";
const s3 = "PHAN THỊ NHẬT AN";

console.log("Direct includes s1:", content.includes(s1));
console.log("Direct includes s3:", content.includes(s3));

const lowerContent = content.toLowerCase();
console.log("Lower includes s1.toLowerCase():", lowerContent.includes(s1.toLowerCase()));

// Check NFC normalize
const normContent = content.normalize('NFC').toLowerCase();
const normS1 = s1.normalize('NFC').toLowerCase();
console.log("Norm lower includes:", normContent.includes(normS1));

// Check index
const idx = normContent.indexOf(normS1);
console.log("Idx:", idx);
if (idx !== -1) {
    console.log("Matched slice:", content.substring(idx, idx + s1.length));
}
