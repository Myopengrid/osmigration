<ul id="migration-tabs" class="nav nav-tabs">
    @foreach($import['tabs'] as $tab_slug => $tab_title)
    <li>
        <a href="#{{$tab_slug}}" data-toggle="tab">{{$tab_title}}</a>
    </li>
    @endforeach
</ul>

<div class="tab-content">
    @foreach($import['tabs'] as $tab_slug => $tab_title)
    <div id="{{$tab_slug}}" class="tab-pane">
        {{ View::make('osmigration::backend.partials.user_table', array('users' => $import['users'], 'status' => $tab_slug, 'groups' => $groups)) }}
    </div>
    @endforeach
</div>

<script>
    $(function () {
        $('#migration-tabs a:first').tab('show');
    })
</script>