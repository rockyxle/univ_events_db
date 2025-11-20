
// FOR EVENT COST
document.addEventListener("DOMContentLoaded", function () {
    const costInput = document.querySelector('input[name="e_cost"]');

    costInput.addEventListener("input", function () {
        let value = this.value.replace(/[^0-9.]/g, "");
       
        const parts = value.split(".");
        if (parts.length > 2) {
            value = parts[0] + "." + parts[1];
        }

       // whole number
        if (parts[0].length > 6) {
            parts[0] = parts[0].slice(0, 6);
        }

        // decimal prt
        if (parts[1] && parts[1].length > 2) {
            parts[1] = parts[1].slice(0, 2);
        }
        //recombine numbers to fomr price
        this.value = parts.join(".");
    });
});


// for limit in event name
const eventName = document.getElementById("e_name");
if (eventName) {
    eventName.addEventListener("input", function () {
        const msg = document.getElementById("eventNameLimitMsg");
        if (msg) msg.style.display = this.value.length >= this.maxLength ? "block" : "none";
    });
}

// for character limit in adding new venue
const newVenue = document.getElementById("new_event_venue");
if (newVenue) {
    newVenue.addEventListener("input", function () {
        const msg = document.getElementById("eventVenueNameLimitMsg");
        if (msg) msg.style.display = this.value.length >= this.maxLength ? "block" : "none";
    });
}

// limit input in organizers
document.addEventListener("DOMContentLoaded", function () {
    const fields = [
        { id: "o_name", msg: "orgNameLimitMsg" },
        { id: "o_contact_person", msg: "orgCPLimitMsg" },
        { id: "o_email", msg: "orgEmailLimitMsg" },
        { id: "o_contact", msg: "orgConNumLimitMsg" },
        { id: "o_password", msg: "orgPassLimitMsg" }
    ];

    fields.forEach(f => {
        const input = document.getElementById(f.id);
        const msg = document.getElementById(f.msg);

        if (input && msg) {
            input.addEventListener("input", function () {
                msg.style.display = this.value.length >= this.maxLength ? "block" : "none";
            });
        }
    });
});

// limit input in participants
document.addEventListener("DOMContentLoaded", function () {
    const fields = [
        { id: "p_lastname", msg: "pLNameLimMsg" },
        { id: "p_firstname", msg: "pFNameLimMsg" },
        { id: "p_initial", msg: "pIniLimMsg" },
        { id: "p_email", msg: "pEmailLimMsg" },
        { id: "p_contact", msg: "pContactLimMsg" }
    ];

    fields.forEach(f => {
        const input = document.getElementById(f.id);
        const msg = document.getElementById(f.msg);

        if (input && msg) {
            input.addEventListener("input", function () {
                msg.style.display = this.value.length >= this.maxLength ? "block" : "none";
            });
        }
    });
});

// events auto clear modal
document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById('exampleModal');
    const form = modal.querySelector("form");

    modal.addEventListener("hidden.bs.modal", function () {
        form.reset();  
        const messages = form.querySelectorAll(".text-danger");
        messages.forEach(msg => msg.style.display = "none");
    });
});

// confirmation before closing and saving events
let preventAutoClear = false;

document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("exampleModal");
    if (!modal) return;

    const form = modal.querySelector("form");
    if (!form) return;

    let formChanged = false;

    form.querySelectorAll("input, select, textarea").forEach(input => {
        input.addEventListener("input", () => {
            formChanged = true;
        });
    });

    // confirmation before close
    modal.addEventListener("hide.bs.modal", function (e) {
        if (formChanged) {
            const confirmClose = confirm("Any unsaved changes will be lost. Are you sure you want to close the form?");
            if (!confirmClose) {
                preventAutoClear = true;  
                e.preventDefault();       
                return;
            }
            formChanged = false;  
        }

        preventAutoClear = false;  
    });

    // autoclear modal content
    modal.addEventListener("hidden.bs.modal", function () {
        if (!preventAutoClear) {
            form.reset();

            const messages = form.querySelectorAll(".text-danger");
            messages.forEach(msg => msg.style.display = "none");
        }
    });
});

// confirm before adding even
function confirmSaveEvent() {
    return confirm("Do you want to save and add this event?");
}

function confirmSaveOrg() {
    return confirm("Do you want to save and add this organizer?");
}
function confirmSaveParticipant() {
    return confirm("Do you want to save and add this participant?");
}
