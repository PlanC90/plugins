$file = "c:\Users\ceyhun\Local Sites\xepmarket\app\public\wp-content\plugins\omnixep-woocommerce\omnixep-woocommerce.php"
$lines = [System.IO.File]::ReadAllLines($file, [System.Text.Encoding]::UTF8)
$new_lines = New-Object System.Collections.Generic.List[string]

$found_dup = $false
$last_line = ""
foreach ($line in $lines) {
    # Skip the second occurrence of the specific if statement
    if (!$found_dup -and $line.Trim() -eq "if (!wc_omnixep_check_terms_acceptance()) {" -and $last_line.Trim() -eq "if (!wc_omnixep_check_terms_acceptance()) {") {
        $found_dup = $true
        continue 
    }
    
    $processed = $line
    # Re-insert the missing $synced and $_GET variables
    if ($processed.Trim() -eq "= get_option('omnixep_terms_synced_to_api', false);") {
        $processed = "        `$synced = get_option('omnixep_terms_synced_to_api', false);"
    }
    if ($processed.Trim() -eq "if (! && isset(['section']) && ['section'] === 'omnixep') {") {
        $processed = "        if (!`$synced && isset(`$_GET['section']) && `$_GET['section'] === 'omnixep') {"
    }
    if ($processed.Contains("isset(['section'])")) {
        $processed = $processed -replace "isset\(\['section'\]\)", "isset(`$_GET['section'])"
        $processed = $processed -replace "\['section'\]", "`$_GET['section']"
        $processed = $processed -replace "if \(! &&", "if (!`$synced &&"
    }

    $new_lines.Add($processed)
    $last_line = $line
}

[System.IO.File]::WriteAllLines($file, $new_lines, (New-Object System.Text.UTF8Encoding($false)))
Write-Output "Done"
