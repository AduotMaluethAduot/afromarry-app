// Dowry Calculator functionality

let dowryCalculator = {
    familySize: 5,
    traditionLevel: 50,
    region: 'east',
    result: null
};

// Load dowry calculator
function loadDowryCalculator() {
    const calculatorContainer = document.getElementById('dowry-calculator');
    if (!calculatorContainer) return;
    
    calculatorContainer.innerHTML = `
        <div class="calculator-container">
            <div class="calculator-field">
                <label for="family-size">Family Size: <span id="family-size-value">${dowryCalculator.familySize}</span> members</label>
                <input type="range" id="family-size" min="1" max="20" value="${dowryCalculator.familySize}" 
                       oninput="updateFamilySize(this.value)">
            </div>
            
            <div class="calculator-field">
                <label for="tradition-level">Tradition Level: <span id="tradition-level-value">${dowryCalculator.traditionLevel}</span>% Traditional</label>
                <input type="range" id="tradition-level" min="0" max="100" value="${dowryCalculator.traditionLevel}" 
                       oninput="updateTraditionLevel(this.value)">
            </div>
            
            <div class="calculator-field">
                <label for="region">Region</label>
                <select id="region" onchange="updateRegion(this.value)">
                    <option value="east" ${dowryCalculator.region === 'east' ? 'selected' : ''}>East Africa</option>
                    <option value="west" ${dowryCalculator.region === 'west' ? 'selected' : ''}>West Africa</option>
                    <option value="southern" ${dowryCalculator.region === 'southern' ? 'selected' : ''}>Southern Africa</option>
                    <option value="north" ${dowryCalculator.region === 'north' ? 'selected' : ''}>North Africa</option>
                    <option value="central" ${dowryCalculator.region === 'central' ? 'selected' : ''}>Central Africa</option>
                </select>
            </div>
            
            <button class="btn-primary btn-large" onclick="calculateDowry()">Calculate Estimate</button>
            
            <div id="dowry-result" class="calculator-result" style="display: none;">
                <h4>Estimated Dowry Amount</h4>
                <div class="amount" id="dowry-amount">$ 0</div>
                <p class="text-xs text-gray-500 mt-2">*This is an estimate. Actual amounts vary by family.</p>
            </div>
        </div>
    `;
}

// Update family size
function updateFamilySize(value) {
    dowryCalculator.familySize = parseInt(value);
    document.getElementById('family-size-value').textContent = value;
}

// Update tradition level
function updateTraditionLevel(value) {
    dowryCalculator.traditionLevel = parseInt(value);
    document.getElementById('tradition-level-value').textContent = value;
}

// Update region
function updateRegion(value) {
    dowryCalculator.region = value;
}

// Calculate dowry
function calculateDowry() {
    const baseAmount = 50000;
    const familyMultiplier = dowryCalculator.familySize * 0.2;
    const traditionMultiplier = dowryCalculator.traditionLevel / 100;
    
    const regionMultipliers = {
        east: 1.2,
        west: 1.0,
        southern: 1.5,
        north: 0.8,
        central: 1.1
    };
    
    const total = baseAmount * (1 + familyMultiplier) * (1 + traditionMultiplier) * regionMultipliers[dowryCalculator.region];
    dowryCalculator.result = Math.round(total);
    
    displayDowryResult();
}

// Display dowry result
function displayDowryResult() {
    const resultContainer = document.getElementById('dowry-result');
    const amountElement = document.getElementById('dowry-amount');
    
    if (resultContainer && amountElement) {
        amountElement.textContent = `$ ${dowryCalculator.result.toLocaleString()}`;
        resultContainer.style.display = 'block';
        
        // Add some animation
        resultContainer.style.opacity = '0';
        resultContainer.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            resultContainer.style.transition = 'all 0.3s ease';
            resultContainer.style.opacity = '1';
            resultContainer.style.transform = 'translateY(0)';
        }, 100);
    }
}

// Get dowry breakdown
function getDowryBreakdown() {
    const baseAmount = 50000;
    const familyMultiplier = dowryCalculator.familySize * 0.2;
    const traditionMultiplier = dowryCalculator.traditionLevel / 100;
    
    const regionMultipliers = {
        east: 1.2,
        west: 1.0,
        southern: 1.5,
        north: 0.8,
        central: 1.1
    };
    
    const regionMultiplier = regionMultipliers[dowryCalculator.region];
    
    return {
        baseAmount: baseAmount,
        familyAdjustment: baseAmount * familyMultiplier,
        traditionAdjustment: baseAmount * traditionMultiplier,
        regionAdjustment: baseAmount * (regionMultiplier - 1),
        total: baseAmount * (1 + familyMultiplier) * (1 + traditionMultiplier) * regionMultiplier
    };
}

// Export dowry calculation
function exportDowryCalculation() {
    const breakdown = getDowryBreakdown();
    const data = {
        familySize: dowryCalculator.familySize,
        traditionLevel: dowryCalculator.traditionLevel,
        region: dowryCalculator.region,
        breakdown: breakdown,
        calculatedAt: new Date().toISOString()
    };
    
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'dowry-calculation.json';
    a.click();
    URL.revokeObjectURL(url);
}

// Share dowry calculation
function shareDowryCalculation() {
    if (navigator.share) {
        const breakdown = getDowryBreakdown();
        navigator.share({
            title: 'My Dowry Calculation - AfroMarry',
            text: `I calculated my dowry using AfroMarry's calculator. Estimated amount: $ ${Math.round(breakdown.total).toLocaleString()}`,
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        const breakdown = getDowryBreakdown();
        const text = `My Dowry Calculation - AfroMarry\nEstimated amount: $ ${Math.round(breakdown.total).toLocaleString()}\nFamily size: ${dowryCalculator.familySize}\nTradition level: ${dowryCalculator.traditionLevel}%\nRegion: ${dowryCalculator.region}`;
        
        navigator.clipboard.writeText(text).then(() => {
            showMessage('Calculation copied to clipboard!', 'success');
        });
    }
}

// Reset calculator
function resetDowryCalculator() {
    dowryCalculator = {
        familySize: 5,
        traditionLevel: 50,
        region: 'east',
        result: null
    };
    
    loadDowryCalculator();
    
    const resultContainer = document.getElementById('dowry-result');
    if (resultContainer) {
        resultContainer.style.display = 'none';
    }
}

// Save calculation (if user is logged in)
async function saveDowryCalculation() {
    if (!currentUser) {
        showSignupModal();
        return;
    }
    
    try {
        const breakdown = getDowryBreakdown();
        const data = {
            family_size: dowryCalculator.familySize,
            tradition_level: dowryCalculator.traditionLevel,
            region: dowryCalculator.region,
            estimated_amount: Math.round(breakdown.total),
            breakdown: breakdown
        };
        
        const response = await fetch('api/dowry-calculations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('Calculation saved successfully!', 'success');
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error saving calculation:', error);
        showMessage('Error saving calculation', 'error');
    }
}

// Load saved calculations
async function loadSavedCalculations() {
    if (!currentUser) return;
    
    try {
        const response = await fetch('api/dowry-calculations.php');
        const data = await response.json();
        
        if (data.success) {
            displaySavedCalculations(data.data);
        }
    } catch (error) {
        console.error('Error loading saved calculations:', error);
    }
}

// Display saved calculations
function displaySavedCalculations(calculations) {
    // This would display a list of saved calculations
    // Display saved calculations in UI (implementation needed)
}
