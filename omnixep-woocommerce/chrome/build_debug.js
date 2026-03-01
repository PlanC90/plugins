const browserify = require('browserify');
const fs = require('fs');

console.log("Starting build...");
try {
    const b = browserify('./src/wallet_core.js', {
        standalone: 'WalletCore'
    });

    b.bundle()
        .on('error', (err) => {
            const errorLog = `--- ERROR ---\nMessage: ${err.message}\nStack: ${err.stack}\nDetails: ${JSON.stringify(err)}`;
            fs.writeFileSync('build_error.txt', errorLog);
            console.log('Error written to build_error.txt');
        })
        .pipe(fs.createWriteStream('bundle.js'))
        .on('finish', () => console.log('Build complete.'));

} catch (e) {
    console.error("Setup error:", e);
}
