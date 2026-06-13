<div class="presto-table-container">
    <table class="presto-table">
        <thead>
            <tr>
                @foreach($columns as $slug => $label)
                    <th class="col-{{ $slug }}">
                        {{ $label }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    @foreach($columns as $slug => $label)
                        <td>
                            {{ $item[$slug] ?? '' }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
