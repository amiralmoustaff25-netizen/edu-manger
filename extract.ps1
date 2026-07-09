Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
try {
    $zip = [System.IO.Compression.ZipFile]::OpenRead('C:/Users/moust/edu-manager/storage/app/cdc.docx')
    $entry = $zip.GetEntry('word/document.xml')
    $reader = New-Object System.IO.StreamReader($entry.Open())
    $xml = $reader.ReadToEnd()
    $reader.Close()
    $zip.Dispose()
    $xml = [regex]::Replace($xml, '<w:p[^>]*>', [Environment]::NewLine)
    $xml = [regex]::Replace($xml, '<w:tab[^>]*/>', "	")
    $xml = [regex]::Replace($xml, '<w:br[^>]*/>', [Environment]::NewLine)
    $xml = [regex]::Replace($xml, '<[^>]+>', '')
    $xml = [System.Net.WebUtility]::HtmlDecode($xml)
    Set-Content -Path 'C:/Users/moust/edu-manager/storage/app/cdc.txt' -Value $xml -Encoding UTF8
    'OK ' + $xml.Length
} catch { $_.Exception.Message | Out-String | Set-Content -Path 'C:/Users/moust/edu-manager/storage/app/cdc_err.txt'; 'ERR' }
