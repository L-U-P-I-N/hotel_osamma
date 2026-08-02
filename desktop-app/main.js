const { app, BrowserWindow, Menu } = require('electron');

const APP_URL = 'http://localhost/';
const MAX_RETRY_SECONDS = 60;

Menu.setApplicationMenu(null);

function createWindow() {
    const win = new BrowserWindow({
        width: 1280,
        height: 800,
        title: 'نظام الفندق',
        autoHideMenuBar: true,
        webPreferences: {
            contextIsolation: true,
            nodeIntegration: false,
        },
    });

    win.setMenuBarVisibility(false);

    let attempts = 0;
    const tryLoad = () => {
        win.loadURL(APP_URL).catch(() => {
            attempts += 1;
            if (attempts >= MAX_RETRY_SECONDS) {
                win.loadURL(
                    'data:text/html;charset=utf-8,' +
                    encodeURIComponent(`
                        <html dir="rtl" lang="ar">
                        <body style="font-family:Tahoma,Arial;text-align:center;padding-top:100px;background:#1e293b;color:#f1f5f9;">
                            <h1>تعذّر الوصول للنظام</h1>
                            <p>تأكد أن خدمة الويب (HotelOsammaApache) تعمل، ثم أعد فتح البرنامج.</p>
                        </body>
                        </html>
                    `)
                );
                return;
            }
            setTimeout(tryLoad, 1000);
        });
    };

    // Show a lightweight "starting up" page immediately, then swap to the
    // real app once Apache (started as a Windows Service, which can take a
    // few seconds right after the PC boots) actually answers.
    win.loadURL(
        'data:text/html;charset=utf-8,' +
        encodeURIComponent(`
            <html dir="rtl" lang="ar">
            <body style="font-family:Tahoma,Arial;text-align:center;padding-top:100px;background:#1e293b;color:#f1f5f9;">
                <h2>جاري تشغيل النظام...</h2>
            </body>
            </html>
        `)
    );
    tryLoad();
}

app.whenReady().then(createWindow);

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') app.quit();
});

app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
});
