<!-- MODAL: ADD RESIDENT -->
<div id="addModal" class="modal-overlay" style="display: none;">
    <div class="modal-card">
        <button class="modal-close" onclick="document.getElementById('addModal').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        <div class="panel-title" style="margin-bottom: 20px;"><i class="fa-solid fa-user-plus" style="color: var(--primary);"></i> Register New Resident Profile</div>
        <form class="form-grid" method="POST" action="backend/add_resident.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="field-group"><label>First Name</label><input type="text" name="first_name" required></div>
            <div class="field-group"><label>Middle Name</label><input type="text" name="middle_name"></div>
            <div class="field-group"><label>Last Name</label><input type="text" name="last_name" required></div>
            <div class="field-group"><label>Age</label><input type="number" name="age" required></div>
            <div class="field-group"><label>Gender</label><select name="gender" required><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
            <div class="field-group"><label>Contact Number</label><input type="text" name="contact_number" required></div>
            <div class="field-group wide"><label>Address / Purok</label><input type="text" name="address" required></div>
            <button type="submit" name="btn_save_resident" class="btn-main"><i class="fa-solid fa-floppy-disk"></i> Commit Record</button>
        </form>
    </div>
</div>

<!-- MODAL: ADD BLOTTER -->
<div id="blotterModal" class="modal-overlay" style="display: none;">
    <div class="modal-card">
        <button class="modal-close" onclick="document.getElementById('blotterModal').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        <div class="panel-title" style="margin-bottom: 20px;"><i class="fa-solid fa-triangle-exclamation" style="color: var(--warning);"></i> File New Blotter Complaint</div>
        <form class="form-grid" method="POST" action="backend/add_blotter.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="field-group"><label>Complainant Name</label><input type="text" name="complainant_name" required></div>
            <div class="field-group"><label>Respondent Name</label><input type="text" name="respondent_name" required></div>
            <div class="field-group"><label>Incident Type</label><input type="text" name="incident_type" required placeholder="e.g. Unruly Behavior, Property Dispute"></div>
            <div class="field-group"><label>Case Status</label><select name="status"><option value="Pending">Pending</option><option value="Settled">Settled</option></select></div>
            <div class="field-group wide"><label>Incident Details / Narrative</label><textarea name="details" rows="3" required placeholder="Describe what happened..."></textarea></div>
            <button type="submit" name="btn_save_blotter" class="btn-main" style="background: linear-gradient(135deg, var(--warning), #d97706);"><i class="fa-solid fa-floppy-disk"></i> Save Blotter Record</button>
        </form>
    </div>
</div>

<!-- MODAL: EDIT RESIDENT -->
<?php if($isEditing): ?>
<div class="modal-overlay" style="display: flex;">
    <div class="modal-card">
        <a href="index.php?tab=residents" class="modal-close"><i class="fa-solid fa-xmark"></i></a>
        <div class="panel-title" style="margin-bottom: 20px;"><i class="fa-solid fa-pen-to-square" style="color: var(--warning);"></i> Update Resident Profile</div>
        <form class="form-grid" method="POST" action="backend/update_resident.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="resident_id" value="<?php echo $editData['resident_id']; ?>">
            <div class="field-group"><label>First Name</label><input type="text" name="first_name" required value="<?php echo htmlspecialchars($editData['first_name']); ?>"></div>
            <div class="field-group"><label>Middle Name</label><input type="text" name="middle_name" value="<?php echo htmlspecialchars($editData['middle_name']); ?>"></div>
            <div class="field-group"><label>Last Name</label><input type="text" name="last_name" required value="<?php echo htmlspecialchars($editData['last_name']); ?>"></div>
            <div class="field-group"><label>Age</label><input type="number" name="age" required value="<?php echo $editData['age']; ?>"></div>
            <div class="field-group"><label>Gender</label><select name="gender" required><option value="Male" <?php echo ($editData['gender']=='Male')?'selected':'';?>>Male</option><option value="Female" <?php echo ($editData['gender']=='Female')?'selected':'';?>>Female</option></select></div>
            <div class="field-group"><label>Contact Number</label><input type="text" name="contact_number" required value="<?php echo htmlspecialchars($editData['contact_number']); ?>"></div>
            <div class="field-group wide"><label>Address / Purok</label><input type="text" name="address" required value="<?php echo htmlspecialchars($editData['address']); ?>"></div>
            <button type="submit" name="btn_save_resident" class="btn-main" style="background: linear-gradient(135deg, var(--warning), #d97706);"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </form>
    </div>
</div>
<?php endif; ?>
