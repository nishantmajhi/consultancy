document.addEventListener("DOMContentLoaded", () => {
  const skillList = document.getElementById("skill_list");
  skillList.innerHTML = "";

  skills.forEach((skill) => {
    const option = document.createElement("option");
    option.value = skill;
    skillList.appendChild(option);
  });
});

function updateSkillList() {
  const skillInput = document.getElementById("skill_preferences");
  const selectedSkill = skillInput.value.trim();

  if (selectedSkill !== "") {
    if (!getExistingSkills().includes(selectedSkill))
      createCheckBox(selectedSkill);
  }

  skillInput.value = "";
}

function getExistingSkills() {
  return Array.from(
    document.querySelectorAll('input[type="checkbox"][name="skills[]"]')
  ).map((checkbox) => checkbox.value);
}

function createCheckBox(selectedSkill) {
  const chosenSkills = document.getElementById("chosen_skills");
  chosenSkills.style.display = "flex";

  const labelElement = createSkillLabel(selectedSkill, chosenSkills);
  const inputElement = createSkillInput(selectedSkill);

  labelElement.appendChild(inputElement);
  chosenSkills.appendChild(labelElement);
}

function createSkillLabel(selectedSkill, chosenSkills) {
  const labelElement = document.createElement("label");
  labelElement.className = "skill";
  labelElement.textContent = selectedSkill;

  labelElement.addEventListener("click", () => {
    labelElement.remove();

    if (getExistingSkills().length === 0) {
      chosenSkills.style.display = "none";
    }
  });

  return labelElement;
}

function createSkillInput(selectedSkill) {
  const inputElement = document.createElement("input");
  inputElement.type = "checkbox";
  inputElement.checked = true;
  inputElement.name = "skills[]";
  inputElement.value = selectedSkill;

  return inputElement;
}

const skills = [
  "Communication",
  "Teamwork",
  "Problem-solving",
  "Adaptability",
  "Time Management",
  "Critical Thinking",
  "Leadership",
  "Creativity",
  "Conflict Resolution",
  "Emotional Intelligence",
  "Technical Writing",
  "Data Analysis",
  "Coding",
  "Project Management",
  "Presentation Skills",
  "Customer Service",
  "Networking",
  "Attention to Detail",
  "Sales Skills",
  "Digital Marketing",
];
