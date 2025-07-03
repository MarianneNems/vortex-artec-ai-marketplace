# Node.js Installation Guide for VORTEX AI Marketplace

## 🚀 Quick Installation (Recommended)

### Method 1: Direct Download (Easiest)
1. **Go to**: https://nodejs.org/en/download/
2. **Download**: "Windows Installer (.msi)" - **LTS version** (recommended)
3. **Run the installer** and follow these steps:
   - ✅ Accept the license agreement
   - ✅ Choose installation directory (default is fine: `C:\Program Files\nodejs\`)
   - ✅ **IMPORTANT**: Check "Add to PATH" option
   - ✅ Install npm package manager (should be checked by default)
   - ✅ Install tools for native modules (recommended)
4. **Restart PowerShell** after installation
5. **Test installation**: `node --version` and `npm --version`

### Method 2: Chocolatey (If you have it)
```powershell
# Install Node.js with Chocolatey
choco install nodejs

# Restart PowerShell and test
node --version
npm --version
```

### Method 3: winget (If first attempt failed)
```powershell
# Try alternative winget packages
winget install --id OpenJS.NodeJS.LTS
# OR
winget install --id Microsoft.NodeJS
```

## 🔧 After Installation

### 1. Verify Installation
Open a **new PowerShell window** and run:
```powershell
node --version    # Should show: v20.x.x or v18.x.x
npm --version     # Should show: 10.x.x or 9.x.x
```

### 2. Navigate to Project
```powershell
cd C:\Users\mvill\Documents\marketplace\vortex-ai-marketplace
```

### 3. Install Project Dependencies
```powershell
# Install all dependencies (this will take a few minutes)
npm install

# Verify package.json is found
Get-Content package.json | Select-Object -First 5
```

### 4. Test Build System
```powershell
# Development build
npm run build:dev

# Production build
npm run build

# Run tests
npm test

# Start development server
npm run dev
```

## 🚨 Troubleshooting

### Issue: "npm is not recognized"
**Solution**: Node.js PATH not added correctly
1. **Manual PATH Setup**:
   - Open "Environment Variables" in Windows
   - Add `C:\Program Files\nodejs\` to your PATH
   - Restart PowerShell

2. **Alternative**: Use full path temporarily:
   ```powershell
   & "C:\Program Files\nodejs\npm.cmd" --version
   ```

### Issue: Permission Errors
**Solution**: Run PowerShell as Administrator or use:
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Issue: Installation Fails
**Solution**: Try portable version:
1. Download Node.js portable: https://nodejs.org/en/download/
2. Extract to `C:\nodejs\`
3. Add `C:\nodejs\` to PATH manually
4. Restart PowerShell

## 📋 Quick Verification Checklist

Once installed, run these commands in PowerShell:

```powershell
# ✅ Node.js version (should be 18.x or 20.x)
node --version

# ✅ npm version (should be 9.x or 10.x)
npm --version

# ✅ Change to project directory
cd C:\Users\mvill\Documents\marketplace\vortex-ai-marketplace

# ✅ Check package.json exists
Test-Path package.json

# ✅ Install dependencies
npm install

# ✅ Run build
npm run build

# ✅ Success message
Write-Host "🎉 Node.js and build system ready!" -ForegroundColor Green
```

## 🎯 Next Steps After Installation

1. **Install Dependencies**: `npm install` (one time)
2. **Development Workflow**: `npm run dev` (for development)
3. **Production Build**: `npm run build` (for deployment)
4. **Run Tests**: `npm test` (to verify functionality)
5. **Code Quality**: `npm run lint` and `npm run format`

---

**Need help?** 
- Check [BUILD_SYSTEM.md](BUILD_SYSTEM.md) for complete build documentation
- Node.js Official Docs: https://nodejs.org/en/docs/
- npm Documentation: https://docs.npmjs.com/ 