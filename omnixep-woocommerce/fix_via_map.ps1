$file = "c:\Users\ceyhun\Local Sites\xepmarket\app\public\wp-content\plugins\omnixep-woocommerce\omnixep-woocommerce.php"
$mapping = Get-Content "c:\Users\ceyhun\Local Sites\xepmarket\app\public\wp-content\plugins\omnixep-woocommerce\mapping.txt" -Encoding UTF8
$content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)

foreach ($line in $mapping) {
    if ($line.Contains("|")) {
        $parts = $line.Split("|")
        $search = $parts[0]
        $replace = $parts[1]
        if ($search -ne "" -and $content.Contains($search)) {
            $content = $content.Replace($search, $replace)
            Write-Output "Fixed occurrences of $search"
        }
    }
}

[System.IO.File]::WriteAllText($file, $content, (New-Object System.Text.UTF8Encoding($false)))
Write-Output "Done"
