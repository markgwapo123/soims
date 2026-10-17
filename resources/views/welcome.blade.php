<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>SOIMS | Supply Office Inventory Management System</title>
        <style>
            :root {
                --blue: #2d78b7;
                --blue-dark: #1d527f;
                --ink: #1f2933;
                --muted: #66727f;
                --paper: #f4f6f5;
                --surface: #ffffff;
                --line: #dbe3e8;
            }
            * { box-sizing: border-box; }
            body { margin: 0; min-height: 100vh; background: var(--paper); color: var(--ink); font-family: Georgia, 'Times New Roman', serif; }
            .shell { width: min(1080px, calc(100% - 40px)); margin: 0 auto; }
            header { display: flex; align-items: center; justify-content: space-between; padding: 24px 0; border-bottom: 1px solid var(--line); }
            .brand { display: flex; gap: 13px; align-items: center; }
            .mark { display: grid; place-items: center; width: 42px; height: 42px; border-radius: 8px; background: var(--blue); color: white; font: 700 16px Arial, sans-serif; letter-spacing: 0; }
            .brand strong { display: block; font-size: 17px; }
            .brand span { color: var(--muted); font: 12px Arial, sans-serif; }
            main { display: grid; grid-template-columns: 1.1fr .9fr; gap: 28px; padding: 72px 0; }
            .eyebrow { color: var(--blue-dark); font: 700 12px Arial, sans-serif; letter-spacing: .08em; text-transform: uppercase; }
            h1 { max-width: 620px; margin: 14px 0 18px; font-size: clamp(38px, 6vw, 68px); line-height: 1.02; font-weight: 400; }
            p { color: var(--muted); font: 17px/1.65 Arial, sans-serif; }
            .panel { align-self: center; padding: 30px; background: var(--surface); border: 1px solid var(--line); border-top: 4px solid var(--blue); box-shadow: 0 12px 30px rgba(32, 62, 84, .08); }
            .panel h2 { margin: 0 0 10px; font-size: 25px; font-weight: 400; }
            .panel p { margin: 0 0 22px; font-size: 14px; }
            .status { display: flex; align-items: center; gap: 9px; color: var(--blue-dark); font: 700 13px Arial, sans-serif; }
            .dot { width: 9px; height: 9px; border-radius: 50%; background: #4c9a67; }
            footer { padding: 20px 0; color: var(--muted); border-top: 1px solid var(--line); font: 12px Arial, sans-serif; }
            @media (max-width: 700px) { main { grid-template-columns: 1fr; padding: 48px 0; } header { align-items: flex-start; } .brand span { display: block; max-width: 180px; } }
        </style>
    </head>
    <body>
        <div class="shell">
            <header>
                <div class="brand">
                    <div class="mark">SO</div>
                    <div><strong>SOIMS</strong><span>Supply Office Inventory Management System</span></div>
                </div>
            </header>
            <main>
                <section>
                    <div class="eyebrow">WVSU Himamaylan City Campus</div>
                    <h1>Supply operations, ready for a clear foundation.</h1>
                    <p>The application foundation is installed and ready for the approved requirements, architecture, and database phases.</p>
                </section>
                <aside class="panel">
                    <h2>Development foundation</h2>
                    <p>SOIMS is configured for local Laravel development. Business modules will be added only in their approved phases.</p>
                    <div class="status"><span class="dot"></span>Application ready</div>
                </aside>
            </main>
            <footer>West Visayas State University - Himamaylan City Campus</footer>
        </div>
    </body>
</html>
