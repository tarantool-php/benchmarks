#!/usr/bin/env tarantool

local listen = os.getenv('TNT_LISTEN_URI')

box.cfg {
    listen = (listen == '' or listen == nil) and 3301 or listen,
    wal_mode = 'none',
    snap_dir = '/tmp',
    readahead = 1 * 1024 * 1024
}

box.schema.user.grant('guest', 'read,write,execute,create,drop,alter', 'universe', nil, {if_not_exists = true})


function create_fixtures()
    local space_name = 'items'
    local space

    if box.space[space_name] then
        box.space[space_name]:drop()
    end

    space = box.schema.space.create(space_name, {id = 555, temporary = true})
    space:create_index('primary', {type = 'hash', parts = {1, 'unsigned'}, sequence = true})

    for i = 1, 100000 do
        space:replace{i, 'tuple_' .. i}
    end
end
