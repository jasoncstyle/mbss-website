# Deploy MBSS theme + plugin to SiteGround dev site via SCP
# Usage: .\scripts\deploy-to-siteground.ps1

$ErrorActionPreference = "Stop"

$RepoRoot = Split-Path -Parent $PSScriptRoot
$Key = "$env:USERPROFILE\.ssh\mbss_siteground"
$HostName = "ssh.myrtlebeachsailingschool.com"
$Port = 18765
$User = "u165-ljougq6crppn"
$Remote = "${User}@${HostName}"
$DevWp = "~/www/dev.myrtlebeachsailingschool.com/public_html/wp-content"

Write-Host "Deploying MBSS theme..."
scp -i $Key -P $Port -r "$RepoRoot\theme\mbss-theme" "${Remote}:${DevWp}/themes/"

Write-Host "Deploying MBSS Operations plugin..."
scp -i $Key -P $Port -r "$RepoRoot\plugin\mbss-operations" "${Remote}:${DevWp}/plugins/"

Write-Host "Done. Refresh https://dev.myrtlebeachsailingschool.com"
