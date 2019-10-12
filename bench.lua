#!/usr/bin/env tarantool

local listen = os.getenv('TNT_LISTEN_URI')

box.cfg {
    listen = (listen == '' or listen == nil) and 3301 or listen,
    wal_mode = 'none',
    snap_dir = '/tmp',
    readahead = 1 * 1024 * 1024
}

box.schema.user.grant('guest', 'read,write,execute,create,drop,alter', 'universe', nil, {if_not_exists = true})

function create_space(config)
    local space

    if box.space[config.space_name] then
        box.space[config.space_name]:drop()
    end

    space = box.schema.space.create(config.space_name, {id = config.space_id, temporary = true})
    space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}, sequence = true})
    space:format({{name = 'id', type = 'unsigned'}, {name = 'name', type = 'string', is_nullable = false}})

    return space
end

function load_fixtures(config)
    local space = create_space(config)

    for i = 1, config.row_count do
        space:replace{i, 'tuple_' .. i}
    end
end