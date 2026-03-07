$file = "c:\Users\ceyhun\Local Sites\xepmarket\app\public\wp-content\plugins\omnixep-woocommerce\omnixep-woocommerce.php"
$lines = [System.IO.File]::ReadAllLines($file, [System.Text.Encoding]::UTF8)
$new_lines = New-Object System.Collections.Generic.List[string]

$last_line = ""
foreach ($line in $lines) {
    # Skip if it's a duplicate of the previous line (specific to the if statement we messed up)
    if ($line.Trim() -eq "if (!wc_omnixep_check_terms_acceptance()) {" -and $last_line.Trim() -eq "if (!wc_omnixep_check_terms_acceptance()) {") {
        continue
    }
    
    $processed = $line
    # General cleanup of mojibake
    $processed = $processed -replace "ÄŸÅ¸â€œâ€ž", "📄"
    $processed = $processed -replace "Ã¢Â Å’", "❌"
    $processed = $processed -replace "ÄŸÅ¸â€ â€ž", "🔄"
    $processed = $processed -replace "ℹ️ Â", "ℹ️"
    
    $new_lines.Add($processed)
    $last_line = $line
}

[System.IO.File]::WriteAllLines($file, $new_lines, (New-Object System.Text.UTF8Encoding($false)))
Write-Output "Cleaned up"
