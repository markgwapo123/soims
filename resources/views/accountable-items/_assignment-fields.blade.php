<div class="soims-field">
    <label class="soims-label" for="assigned_to_user_id">User</label>
    <select class="soims-input" id="assigned_to_user_id" name="assigned_to_user_id">
        <option value="">No user</option>
        @foreach ($users as $user)
            <option value="{{ $user->id }}" @selected((string) old('assigned_to_user_id') === (string) $user->id)>{{ $user->name }}</option>
        @endforeach
    </select>
    @error('assigned_to_user_id')<div class="soims-error">{{ $message }}</div>@enderror
</div>
<div class="soims-field">
    <label class="soims-label" for="assigned_to_department_id">Department</label>
    <select class="soims-input" id="assigned_to_department_id" name="assigned_to_department_id">
        <option value="">No department</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}" @selected((string) old('assigned_to_department_id') === (string) $department->id)>{{ $department->name }}</option>
        @endforeach
    </select>
    @error('assigned_to_department_id')<div class="soims-error">{{ $message }}</div>@enderror
</div>
<div class="soims-field">
    <label class="soims-label" for="assigned_to_office_id">Office</label>
    <select class="soims-input" id="assigned_to_office_id" name="assigned_to_office_id">
        <option value="">No office</option>
        @foreach ($offices as $office)
            <option value="{{ $office->id }}" @selected((string) old('assigned_to_office_id') === (string) $office->id)>{{ $office->name }}</option>
        @endforeach
    </select>
    @error('assigned_to_office_id')<div class="soims-error">{{ $message }}</div>@enderror
</div>
<x-ui.input label="Location" name="location" value="{{ old('location') }}" required />