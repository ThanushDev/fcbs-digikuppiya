function d(){
    let temp1 = document.getElementById("max_hours")
    let max_hours = temp1.value;
    let temp2 = document.getElementById("atd_hours");
    let atd_hours = temp2.value;
    let temp3 = document.getElementById("abs_hours");
    let abs_hours = temp3.value;

    if (max_hours == ''){
        alert("Enter the maximum hours")
    }
    else if (Number(max_hours) <= 0){
        alert("Maximum Hours should be greater than 0")
    }
    else if (Number(max_hours) != Math.floor(Number(max_hours))){
        alert("Maximum Hours should be a whole number.")
    }
    else if (atd_hours == ''){
        alert("Enter the Attended hours")
    }
    else if (Number(atd_hours) < 0){
        alert("Attended Hours should be greater than or equal to 0")
    }
    else if (Number(atd_hours) != Math.floor(Number(atd_hours))){
        alert("Attended Hours should be a whole number.")
    }
    else if (Number(max_hours) < Number(atd_hours)){
        alert("Attended Hours should be lesser than or equal to Maximum hours.")
    }
    else if (Number(abs_hours) < 0){
        alert("Hours going to be absent should be greater than or equal to 0")
    }
    else if (Number(abs_hours) != Math.floor(Number(abs_hours))){
        alert("Hours going to be absent should be a whole number.")
    }
    else{
        let current = document.getElementById("curr_value");
        current.innerHTML = (Number(atd_hours) / Number(max_hours) * 100).toFixed(2) + `%`;

        if (abs_hours == ''){
            abs_hours = 0;
            max_hours = Number(max_hours);
        }
        else{
            max_hours = Number(max_hours);
            max_hours += Number(abs_hours);
        }

        // Calculate final percentage after absences
        let finalPercentage = (Number(atd_hours) / max_hours) * 100;

        // Show eligibility
        let eligibilityElement = document.getElementById("eligibility");
        if (finalPercentage >= 80) {
            eligibilityElement.innerHTML = "Eligible";
            eligibilityElement.style.color = "green"; // Optional styling
        } else {
            eligibilityElement.innerHTML = "Not Eligible";
            eligibilityElement.style.color = "red"; // Optional styling
        }
    }

    temp2.scrollIntoView();
}
