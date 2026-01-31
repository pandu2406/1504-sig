from playwright.sync_api import sync_playwright
import time
import os

def run():
    with sync_playwright() as p:
        # Launch browser (headless=False so user can see it if possible, or True is fine)
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        
        print("Navigating to http://localhost:8001/units/rekap ...")
        page.goto("http://localhost:8001/units/rekap")
        
        # Wait for map to load
        print("Waiting for map container...")
        page.wait_for_selector("#map")
        
        # Wait a bit for tiles to render
        print("Waiting for tiles to render...")
        time.sleep(5)
        
        # Take screenshot
        output_path = os.path.abspath("map_verification.png")
        page.screenshot(path=output_path)
        print(f"Screenshot saved to: {output_path}")
        
        browser.close()

if __name__ == "__main__":
    run()
