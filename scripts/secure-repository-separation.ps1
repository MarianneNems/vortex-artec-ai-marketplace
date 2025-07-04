# 🔒 VORTEX ARTEC - Secure Repository Separation
# Separates proprietary algorithms from public investor repository

param(
    [string]$PublicRepoPath = "vortex-artec-public",
    [string]$PrivateRepoPath = "vortex-artec-private",
    [switch]$DryRun = $false
)

Write-Host "🔒 VORTEX ARTEC - Security Chief: Repository Separation Protocol" -ForegroundColor Yellow
Write-Host "=================================================" -ForegroundColor Yellow

# Define proprietary files and directories that need to be moved to private repo
$ProprietaryItems = @(
    # Core proprietary algorithms
    "vortex-ai-engine/includes/secret-sauce/",
    "includes/class-vortex-runpod-vault-orchestrator.php",
    "wp-content/plugins/marketplace/includes/class-vortex-agent-response-filter.php",
    "marketplace/wp-content/plugins/marketplace/includes/class-vortex-agent-response-filter.php",
    "wp-content/plugins/marketplace/includes/class-vortex-security-protocol.php",
    "marketplace/wp-content/plugins/marketplace/includes/class-vortex-security-protocol.php",
    "wp-content/plugins/marketplace/includes/deep-learning/",
    "marketplace/wp-content/plugins/marketplace/includes/deep-learning/",
    "marketplace/class-vortex-ai-learning.php",
    
    # Private scripts and configurations
    "setup-private-vault-existing-pod.sh",
    "secure-existing-pod.sh",
    "deploy-vortex-private-engine.sh",
    "private_seed_zodiac_module/",
    "private-core/",
    
    # AI Models and algorithms
    "includes/ai-models/",
    "includes/ai-agents/",
    "includes/agents/",
    "server/",
    
    # Blockchain private components
    "blockchain/class-vortex-token-burn.php",
    "blockchain/class-vortex-token-handler.php",
    "blockchain/class-vortex-wallet-connect.php",
    "contracts/",
    
    # Database schemas and private data
    "database/schemas/",
    "includes/database/",
    
    # Private API components
    "api/class-vortex-blockchain-api.php",
    "api/class-vortex-huraii-api.php",
    
    # Configuration files with sensitive data
    "*.env",
    "*.key",
    "*.pem",
    "wp-config.php",
    "config.php"
)

# Define public files that should remain in public repo
$PublicItems = @(
    # Investor materials
    "INVESTOR_PITCH.md",
    "README.md",
    "assets/architecture.svg",
    "assets/vortex-mindmap.svg",
    "assets/svg-to-png-viewer.html",
    "assets/convert-to-png.ps1",
    
    # Documentation
    "docs/",
    ".github/",
    "LEGAL/",
    
    # Public WordPress structure (without proprietary algorithms)
    "public/",
    "templates/",
    "languages/",
    "css/",
    "js/",
    
    # Testing and CI/CD
    "tests/",
    "cypress/",
    ".github/workflows/",
    
    # Package management
    "package.json",
    "package-lock.json",
    "composer.json",
    "composer.lock",
    
    # Build and deployment (non-sensitive)
    "webpack.config.js",
    ".gitignore",
    ".gitattributes"
)

function Write-SecurityLog {
    param([string]$Message, [string]$Level = "INFO")
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logEntry = "[$timestamp] [$Level] $Message"
    
    Write-Host $logEntry -ForegroundColor $(
        switch ($Level) {
            "ERROR" { "Red" }
            "WARNING" { "Yellow" }
            "SUCCESS" { "Green" }
            default { "White" }
        }
    )
    
    # Log to file
    $logFile = "security-separation-log.txt"
    $logEntry | Out-File -FilePath $logFile -Append
}

function Test-ProprietaryContent {
    param([string]$FilePath)
    
    if (-not (Test-Path $FilePath)) {
        return $false
    }
    
    # Check for proprietary keywords in file content
    $proprietaryKeywords = @(
        "secret-sauce",
        "proprietary",
        "PROPRIETARY",
        "neural.network",
        "deep.learning",
        "algorithm.details",
        "private.key",
        "secret.key",
        "VORTEX.SECRET",
        "class.*Secret.*Sauce",
        "RunPod.*Vault",
        "HURAII.*private",
        "CLOE.*algorithm"
    )
    
    try {
        $content = Get-Content $FilePath -Raw -ErrorAction SilentlyContinue
        if ($content) {
            foreach ($keyword in $proprietaryKeywords) {
                if ($content -match $keyword) {
                    Write-SecurityLog "Proprietary content detected in $FilePath - keyword: $keyword" "WARNING"
                    return $true
                }
            }
        }
    } catch {
        Write-SecurityLog "Could not analyze file: $FilePath" "WARNING"
    }
    
    return $false
}

function New-PrivateRepository {
    param([string]$Path)
    
    Write-SecurityLog "Creating private repository structure at $Path" "INFO"
    
    if (-not $DryRun) {
        if (-not (Test-Path $Path)) {
            New-Item -ItemType Directory -Path $Path -Force
        }
        
        # Initialize private repository
        Set-Location $Path
        git init
        git config user.name "VORTEX Security Chief"
        git config user.email "security@vortexartec.com"
        
        # Create private repository structure
        $privateDirs = @(
            "proprietary-algorithms",
            "ai-models",
            "neural-networks",
            "blockchain-private",
            "database-schemas",
            "api-private",
            "security-configs",
            "deployment-scripts",
            "vault-storage"
        )
        
        foreach ($dir in $privateDirs) {
            New-Item -ItemType Directory -Path $dir -Force
        }
        
        # Create private README
        @"
# 🔒 VORTEX ARTEC - Private Repository

**⚠️ CONFIDENTIAL - PROPRIETARY ALGORITHMS**

This repository contains VORTEX ARTEC's proprietary algorithms and trade secrets.

## 🛡️ Security Classification
- **Classification**: CONFIDENTIAL
- **Access Level**: Core Team Only
- **IP Protection**: Trade Secret
- **Distribution**: Restricted

## 📁 Repository Structure
- `proprietary-algorithms/` - Core AI algorithms
- `ai-models/` - Neural network models
- `neural-networks/` - Deep learning architectures
- `blockchain-private/` - Private blockchain components
- `database-schemas/` - Sensitive database structures
- `api-private/` - Private API endpoints
- `security-configs/` - Security configurations
- `deployment-scripts/` - Private deployment scripts
- `vault-storage/` - Secure storage systems

## 🔐 Access Control
Access to this repository is restricted to:
- Marianne Nems (Founder/CEO)
- Core development team
- Authorized contractors with signed NDAs

## 🚨 Security Notice
Unauthorized access, copying, or distribution of this content is strictly prohibited and may result in legal action.

© 2024 VORTEX ARTEC - ALL RIGHTS RESERVED
"@ | Out-File -FilePath "README.md" -Encoding UTF8
        
        # Create .gitignore for private repo
        @"
# Private Repository .gitignore
*.log
*.tmp
*.env.local
*.env.production
node_modules/
.DS_Store
Thumbs.db
*.swp
*.swo
*~
.vscode/
.idea/
*.backup
*.bak
"@ | Out-File -FilePath ".gitignore" -Encoding UTF8
        
        Set-Location ..
    }
    
    Write-SecurityLog "Private repository structure created" "SUCCESS"
}

function Move-ProprietaryContent {
    param([string]$SourcePath, [string]$DestinationPath)
    
    Write-SecurityLog "Moving proprietary content from $SourcePath to $DestinationPath" "INFO"
    
    foreach ($item in $ProprietaryItems) {
        $sourcePath = Join-Path -Path "." -ChildPath $item
        
        if (Test-Path $sourcePath) {
            $relativePath = $item
            $destPath = Join-Path -Path $DestinationPath -ChildPath "proprietary-algorithms/$relativePath"
            
            Write-SecurityLog "Moving: $sourcePath -> $destPath" "INFO"
            
            if (-not $DryRun) {
                $destDir = Split-Path -Path $destPath -Parent
                if (-not (Test-Path $destDir)) {
                    New-Item -ItemType Directory -Path $destDir -Force
                }
                
                try {
                    if (Test-Path $sourcePath -PathType Container) {
                        Copy-Item -Path $sourcePath -Destination $destPath -Recurse -Force
                    } else {
                        Copy-Item -Path $sourcePath -Destination $destPath -Force
                    }
                    
                    # Remove from public repo
                    Remove-Item -Path $sourcePath -Recurse -Force
                    
                    Write-SecurityLog "Successfully moved: $item" "SUCCESS"
                } catch {
                    Write-SecurityLog "Error moving $item : $($_.Exception.Message)" "ERROR"
                }
            }
        } else {
            Write-SecurityLog "Item not found: $sourcePath" "WARNING"
        }
    }
}

function New-PublicRepository {
    param([string]$Path)
    
    Write-SecurityLog "Creating public repository at $Path" "INFO"
    
    if (-not $DryRun) {
        if (-not (Test-Path $Path)) {
            New-Item -ItemType Directory -Path $Path -Force
        }
        
        # Copy public items to new public repo
        foreach ($item in $PublicItems) {
            $sourcePath = Join-Path -Path "." -ChildPath $item
            
            if (Test-Path $sourcePath) {
                $destPath = Join-Path -Path $Path -ChildPath $item
                $destDir = Split-Path -Path $destPath -Parent
                
                if (-not (Test-Path $destDir)) {
                    New-Item -ItemType Directory -Path $destDir -Force
                }
                
                try {
                    if (Test-Path $sourcePath -PathType Container) {
                        Copy-Item -Path $sourcePath -Destination $destPath -Recurse -Force
                    } else {
                        Copy-Item -Path $sourcePath -Destination $destPath -Force
                    }
                    
                    Write-SecurityLog "Copied to public repo: $item" "SUCCESS"
                } catch {
                    Write-SecurityLog "Error copying $item : $($_.Exception.Message)" "ERROR"
                }
            }
        }
    }
}

function Add-SecurityPlaceholders {
    param([string]$PublicPath)
    
    Write-SecurityLog "Adding security placeholders to public repository" "INFO"
    
    # Create placeholder files for removed proprietary components
    $placeholders = @{
        "includes/class-vortex-proprietary-placeholder.php" = @"
<?php
/**
 * VORTEX ARTEC - Proprietary Algorithm Placeholder
 * 
 * This file represents proprietary algorithms that have been moved
 * to a private repository for security purposes.
 * 
 * @package VORTEX_ARTEC
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Proprietary_Placeholder {
    
    public function __construct() {
        add_action('init', array($this, 'security_notice'));
    }
    
    public function security_notice() {
        if (current_user_can('manage_options')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-info"><p>';
                echo '🔒 VORTEX ARTEC proprietary algorithms are securely stored in a private repository. ';
                echo 'Contact security@vortexartec.com for access.';
                echo '</p></div>';
            });
        }
    }
}

new VORTEX_Proprietary_Placeholder();
"@
    }
    
    if (-not $DryRun) {
        foreach ($file in $placeholders.Keys) {
            $filePath = Join-Path -Path $PublicPath -ChildPath $file
            $fileDir = Split-Path -Path $filePath -Parent
            
            if (-not (Test-Path $fileDir)) {
                New-Item -ItemType Directory -Path $fileDir -Force
            }
            
            $placeholders[$file] | Out-File -FilePath $filePath -Encoding UTF8
            Write-SecurityLog "Created placeholder: $file" "SUCCESS"
        }
    }
}

function Update-PublicDocumentation {
    param([string]$PublicPath)
    
    Write-SecurityLog "Updating public documentation" "INFO"
    
    if (-not $DryRun) {
        # Update README to reflect security measures
        $readmePath = Join-Path -Path $PublicPath -ChildPath "README.md"
        if (Test-Path $readmePath) {
            $readmeContent = Get-Content $readmePath -Raw
            
            $securityNotice = @"

## 🔒 Security Notice

This repository contains public-facing materials and documentation for VORTEX ARTEC. 
Proprietary algorithms and trade secrets are maintained in a separate private repository 
for security purposes.

### 🛡️ What's Public vs Private

**Public Repository (This Repo)**:
- Investor pitch materials
- System architecture diagrams
- Public API documentation
- WordPress plugin structure (without proprietary algorithms)
- CI/CD configurations
- Security policies

**Private Repository (Restricted Access)**:
- Proprietary AI algorithms
- Neural network models
- Seed Art Generation systems
- Deep learning architectures
- Private API endpoints
- Security configurations

For access to proprietary components, contact: security@vortexartec.com

---

"@
            
            $updatedContent = $readmeContent -replace "(# .+?\n)", "`$1$securityNotice"
            $updatedContent | Out-File -FilePath $readmePath -Encoding UTF8
            
            Write-SecurityLog "Updated README with security notice" "SUCCESS"
        }
    }
}

# Main execution
try {
    Write-SecurityLog "Starting repository separation process" "INFO"
    
    if ($DryRun) {
        Write-SecurityLog "DRY RUN MODE - No actual changes will be made" "WARNING"
    }
    
    # Step 1: Create private repository
    New-PrivateRepository -Path $PrivateRepoPath
    
    # Step 2: Move proprietary content to private repo
    Move-ProprietaryContent -SourcePath "." -DestinationPath $PrivateRepoPath
    
    # Step 3: Create clean public repository
    New-PublicRepository -Path $PublicRepoPath
    
    # Step 4: Add security placeholders
    Add-SecurityPlaceholders -PublicPath $PublicRepoPath
    
    # Step 5: Update public documentation
    Update-PublicDocumentation -PublicPath $PublicRepoPath
    
    Write-SecurityLog "Repository separation completed successfully" "SUCCESS"
    Write-SecurityLog "Next steps:" "INFO"
    Write-SecurityLog "1. Review separated repositories" "INFO"
    Write-SecurityLog "2. Set up GitHub repositories (public and private)" "INFO"
    Write-SecurityLog "3. Configure access controls" "INFO"
    Write-SecurityLog "4. Update CI/CD pipelines" "INFO"
    Write-SecurityLog "5. Notify team of new repository structure" "INFO"
    
} catch {
    Write-SecurityLog "Error during repository separation: $($_.Exception.Message)" "ERROR"
    exit 1
}

Write-Host "🔒 Repository separation protocol completed!" -ForegroundColor Green 