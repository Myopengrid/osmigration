<div class="row">
    <div style="margin-top:15px;" class="span12">
        @if($status === 'migrationok')
    {{ Form::open(URL::base().'/'.ADM_URI.'/'.'osmigration', 'POST', array('class' => 'form-horizontal')) }}
        <div style="display:none">
            {{ Form::token() }}
        </div>

        <div>
            <legend>Settings</legend>
            <p> Important: After migration all users must reset their password. The migration wont change their grid login password, the users will still be able to login into the grid, but they need to reset their password in order to login into the application and sync both passwords. </p>
            <ul style="float:left">
                <li style="float:left; margin:10px 5px">
                    <label>Import users in chunks of</label>
                    <select name="take"> 
                        <option>100</option>
                        <option>250</option>
                        <option>500</option>
                        <option>1000</option>
                    </select>
                </li>

                <li style="float:left; margin:10px 5px">
                   <label>Set imported users to group</label>
                   <select name="group"> 
                    @foreach($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                    </select>
                </li>
            </ul>
        </div>
        @endif

<table class="table table-bordered">
    <thead>
        <tr>
            <td>UUID</td>
            <td>Avatar First Name</td>
            <td>Avatar Last Name</td>
            <td>Email</td>
            <td></td>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $user)
        @if($user['user']['status'] === $status)
        <tr>
            <td>{{ $user['user']['uuid'] }}</td>
            <td>{{ $user['user']['firstname'] }}</td>
            <td>{{ $user['user']['lastname'] }}</td>
            <td>{{ $user['user']['email'] }}</td>
            <td>{{ $user['user']['message'] }}</td>
            
        </tr>
        @endif
        @endforeach
    </tbody>
</table>

@if($status === 'migrationok')
<div class="form-actions">
    <button class="btn btn-success" type="submit">{{ Lang::line('osmigration::lang.Migrate')->get(ADM_LANG) }}</button>
</div>
{{ Form::close() }}
@endif
</div>
</div>