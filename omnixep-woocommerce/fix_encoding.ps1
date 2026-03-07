$file = 'c:\Users\ceyhun\Local Sites\xepmarket\app\public\wp-content\plugins\omnixep-woocommerce\omnixep-woocommerce.php'
$content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)
$content = $content -replace "ÄŸÅ¸â€œâ€ž", "📄"
$content = $content -replace "Ã¢Â Å’", "❌"
$content = $content -replace "ÄŸÅ¸â€ â€ž", "🔄"
$content = $content -replace "ℹ️ Â", "ℹ️"
[System.IO.File]::WriteAllText($file, $content, (New-Object System.Text.UTF8Encoding($false)))
Write-Output "Fixed $file"
