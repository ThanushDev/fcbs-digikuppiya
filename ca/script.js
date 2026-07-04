// Define the minimum percentage required for each grade
const GRADE_PERCENTAGES = {
    'A+': 75,
    'A': 70,
    'A-': 65,
    'B+': 60,
    'B': 55,
    'B-': 50,
    'C+': 45,
    'C': 40,
    'C-': 35,
    // You can extend this list as per your grading scheme
};

function calculateMarks() {
    // 1. Get input values
    const caMarksInput = document.getElementById('caMarks');
    const desiredGradeSelect = document.getElementById('desiredGrade');
    const resultDiv = document.getElementById('result');

    const caMarks = parseFloat(caMarksInput.value);
    const desiredGrade = desiredGradeSelect.value;
    const requiredPercentage = GRADE_PERCENTAGES[desiredGrade];

    // 2. Input Validation
    if (isNaN(caMarks) || caMarks < 0 || caMarks > 35) {
        resultDiv.innerHTML = '<span style="color: red;">Please enter a valid CA mark between 0 and 35.</span>';
        return;
    }
    if (!desiredGrade || !requiredPercentage) {
        resultDiv.innerHTML = '<span style="color: red;">Please select a desired grade.</span>';
        return;
    }

    // 3. Calculation
    
    // Step 3a: Calculate the minimum total marks needed for the desired grade
    const requiredTotalMarks = requiredPercentage; // Since percentage is out of 100

    // Step 3b: Calculate the marks needed from the FINAL PAPER (out of 65)
    // Total Marks (out of 100) = CA Marks (out of 35) + Paper Contribution (out of 65)
    const neededPaperContribution = requiredTotalMarks - caMarks;

    // 4. Handle edge cases (e.g., impossible to achieve the grade)
    if (neededPaperContribution < 0) {
        // If neededPaperContribution is negative, it means the CA marks alone are enough
        resultDiv.innerHTML = `
            🎉 **Congratulations!** 🎉<br>
            Your current **CA Marks (${caMarks})** are already high enough to guarantee an **${desiredGrade}** or better, 
            even if you score 0 on the paper.
        `;
        return;
    }
    
    // Step 3c: Convert the needed Paper Contribution (out of 65) back to a Paper Mark (out of 100)
    // Paper Contribution = Paper Mark (out of 100) * (65 / 100)
    // Paper Mark (out of 100) = Paper Contribution / (65 / 100)
    const neededPaperMarkOutOf100 = neededPaperContribution / 0.65;


    // 5. Display Result
    let resultHTML;

    if (neededPaperMarkOutOf100 > 100) {
        // If the required mark is over 100, the grade is impossible
        resultHTML = `
            ⚠️ **Grade IMPOSSIBLE!** ⚠️<br>
            To get an **${desiredGrade}** (min ${requiredPercentage}%), you need ${neededPaperContribution.toFixed(2)} marks out of 65 from the paper. 
            This would require you to score **${neededPaperMarkOutOf100.toFixed(2)} out of 100** on the final paper, which is impossible.
        `;
    } else {
        // Successful calculation
        resultHTML = `
            🎯 **To achieve an ${desiredGrade}**: 🎯<br>
            You need a minimum of **${neededPaperMarkOutOf100.toFixed(2)} out of 100** on the final paper.
        `;
    }

    resultDiv.innerHTML = resultHTML;
}