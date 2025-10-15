async function submitFormData(formData) {
  try {
    const response = await fetch(`${window.location.origin}/api/add/job.php`, {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (response.ok) {
      toastDialog("success", "Job added successfully!");
      setTimeout(() => {
        window.location.href = `${window.location.origin}/admin/`;
      }, 3000);
      return true;
    } else {
      toastDialog("error", "Failed to add the job!");
      return false;
    }
  } catch (error) {
    toastDialog("error", "An error occurred while adding the job.");
    console.error("Error:", error);
    return false;
  }
}

function buildFormData({
  name,
  address,
  position,
  mobileNumber,
  minimumSalary,
  deadline,
  skillPreferences,
}) {
  let formData = new FormData();
  formData.append("name", name);
  formData.append("address", address);
  formData.append("mobile_num", mobileNumber);
  formData.append("position", position);
  formData.append("minimumSalary", minimumSalary);
  formData.append("deadline", deadline);

  skillPreferences.forEach((skill) => {
    formData.append("skillPreferences[]", skill);
  });

  return formData;
}

function formIsValid(name, address, mobileNumber, position, skillPreferences) {
  if (
    !name ||
    !address ||
    !mobileNumber ||
    !position ||
    skillPreferences.length === 0
  ) {
    return false;
  } else {
    return true;
  }
}

function getFormData() {
  const form = document.forms["new_job"];
  
  const name = form.elements["name"].value;
  const address = form.elements["address"].value;
  const mobileNumber = form.elements["mobileNumber"].value;
  const position = form.elements["position"].value;
  const minimumSalary = form.elements["minimumSalary"].value;
  const deadline = form.elements["deadline"].value;

  const skills = document.querySelectorAll('input[name="skills[]"]:checked');
  let skillPreferences = Array.from(skills).map((checkbox) => checkbox.value);

  console.log({
    name,
    address,
    mobileNumber,
    position,
    minimumSalary,
    deadline,
    skillPreferences,
  });
  return {
    name,
    address,
    mobileNumber,
    position,
    minimumSalary,
    deadline,
    skillPreferences,
  };
}


function submitForm() {
  document.querySelectorAll("#actions button").forEach((btn) => {
    btn.disabled = true;
  });

  const {
    name,
    address,
    mobileNumber,
    position,
    minimumSalary,
    deadline,
    skillPreferences,
  } = getFormData();

  if (!formIsValid(name, address, mobileNumber, position, skillPreferences)) {
    alertDialog("Error",
      'Add every detail marked by <sub style="color:red; font-size:1.5rem">*</sub> before submitting the form!'
    );
    return;
  }

  const formData = buildFormData({
    name,
    address,
    mobileNumber,
    position,
    minimumSalary,
    deadline,
    skillPreferences,
  });

  if(!submitFormData(formData)){
    document.querySelectorAll("#actions button").forEach((btn) => {
      btn.disabled = false;
    });
  }
}
