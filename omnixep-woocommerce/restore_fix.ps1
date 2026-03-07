$file = "c:\Users\ceyhun\Local Sites\xepmarket\app\public\wp-content\plugins\omnixep-woocommerce\omnixep-woocommerce.php"
$content = [System.IO.File]::ReadAllLines($file, [System.Text.Encoding]::UTF8)

$emoji_doc = [char]0xD83D + [char]0xDCC4
$emoji_warn = [char]0x26A0
$emoji_check = [char]0x2705
$emoji_info = [char]0x2139 + [char]0xFE0F
$dq = [char]34
$sq = [char]39

$new_lines = New-Object System.Collections.Generic.List[string]
# Line 412 was the duplicate start.
for ($i = 0; $i -lt 412; $i++) { $new_lines.Add($content[$i]) }

$new_lines.Add("    if (!wc_omnixep_check_terms_acceptance()) {")
$new_lines.Add("        ?>")
$new_lines.Add("        <div class=" + $dq + "notice notice-error is-dismissible" + $dq + " style=" + $dq + "border-left-width: 5px; border-left-color: #d63638; padding: 20px;" + $dq + ">")
$new_lines.Add("            <h2 style=" + $dq + "margin-top: 0;" + $dq + ">" + $emoji_warn + "  OmniXEP Payment Gateway - Terms of Service Required</h2>")
$new_lines.Add("            <p style=" + $dq + "font-size: 14px; line-height: 1.6;" + $dq + ">")
$new_lines.Add("                <strong>IMPORTANT:</strong> You must read and accept the Terms of Service before using the OmniXEP Payment Gateway.")
$new_lines.Add("            </p>")
$new_lines.Add("            <p style=" + $dq + "font-size: 13px; color: #666; line-height: 1.6;" + $dq + ">")
$new_lines.Add("                The Terms of Service include important information about:")
$new_lines.Add("            </p>")
$new_lines.Add("            <ul style=" + $dq + "font-size: 13px; color: #666; line-height: 1.8;" + $dq + ">")
$new_lines.Add("                <li>" + $emoji_check + " 0.8% commission fee structure</li>")
$new_lines.Add("                <li>" + $emoji_check + " Security responsibilities and wallet management</li>")
$new_lines.Add("                <li>" + $emoji_check + " Liability limitations and risk acknowledgments</li>")
$new_lines.Add("                <li>" + $emoji_check + " Legal protections for both merchant and developer</li>")
$new_lines.Add("            </ul>")
$new_lines.Add("            <p style=" + $dq + "margin-top: 15px;" + $dq + ">")
$new_lines.Add("                <a href=" + $dq + "<?php echo admin_url(" + $dq + "admin.php?page=omnixep-terms" + $dq + "); ?>" + $dq + " class=" + $dq + "button button-primary" + $dq + " style=" + $dq + "background: #d63638; border-color: #d63638; font-size: 14px; height: auto; padding: 10px 20px;" + $dq + ">")
$new_lines.Add("                    " + $emoji_doc + " Read & Accept Terms of Service")
$new_lines.Add("                </a>")
$new_lines.Add("            </p>")
$new_lines.Add("        </div>")
$new_lines.Add("        <?php")
$new_lines.Add("    } else {")
$new_lines.Add("        // Check if synced to API")
$new_lines.Add("        `$synced = get_option(" + $sq + "omnixep_terms_synced_to_api" + $sq + ", false);")
$new_lines.Add("        if (!`$synced && isset(`$_GET[" + $sq + "section" + $sq + "]) && `$_GET[" + $sq + "section" + $sq + "] === " + $sq + "omnixep" + $sq + ") {")
$new_lines.Add("            ?>")
$new_lines.Add("            <div class=" + $dq + "notice notice-info is-dismissible" + $dq + " style=" + $dq + "border-left-width: 5px; border-left-color: #4dabf7; padding: 15px;" + $dq + ">")
$new_lines.Add("                <p style=" + $dq + "margin: 0;" + $dq + ">")
$new_lines.Add("                    <strong>" + $emoji_info + "  Terms Acceptance Not Synced:</strong> ")
$new_lines.Add("                    Your terms acceptance hasn't been sent to the API yet. ")
$new_lines.Add("                    <a href=" + $dq + "<?php echo admin_url(" + $dq + "admin.php?page=omnixep-sync-terms" + $dq + "); ?>" + $dq + " style=" + $dq + "font-weight: 600;" + $dq + ">")
$new_lines.Add("                        Click here to sync now " + [char]0x2192)
$new_lines.Add("                    </a>")
$new_lines.Add("                </p>")
$new_lines.Add("            </div>")
$new_lines.Add("            <?php")
$new_lines.Add("        }")
$new_lines.Add("    }")
$new_lines.Add("}")

# In the current mess, the block ends around line 452.
for ($i = 453; $i -lt $content.Count; $i++) { $new_lines.Add($content[$i]) }

[System.IO.File]::WriteAllLines($file, $new_lines, (New-Object System.Text.UTF8Encoding($false)))
Write-Output "Done"
