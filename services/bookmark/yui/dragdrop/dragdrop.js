YUI.add('moodle-block_recommender_service_bookmark-dragdrop', function(Y) {

    var MOVEICON = {'pix':"i/move_2d",'component':'moodle'},
        WAITICON = {'pix':"i/loading_small",'component':'moodle'},
        AJAXURL = '/blocks/recommender/services/bookmark/ajax.php';

    var DRAGDROP = function() {
        DRAGDROP.superclass.constructor.apply(this, arguments);
    };

    Y.extend(DRAGDROP, Y.Base, {

        goingUp : null,
        lastY   : null,

        initializer : function(params) {

            Y.Node.all('li.bookmark_item').each(function(node) {
                // Add dragger icon
                node.replaceChild(this.getDragElement(), node.one('.bookmark_handle'));

                // Make each li element in the lists draggable
                var dd = new Y.DD.Drag({
                    node: node,
                    //Make each li a Drop target too
                    target: true
                }).plug(Y.Plugin.DDProxy, {
                    //Don't move the node at the end of the drag
                    moveOnEnd: false
                }).plug(Y.Plugin.DDConstrained, {
                    //Keep it inside the .block_recommender_service_bookmark_area
                    constrain2node: '.block_recommender_service_bookmark_area',
                    stickY: true
                });
                dd.addHandle('.bookmark_handle');

            }, this);

            //Create list drop targets as well
            Y.Node.all('ul.block_recommender_service_bookmark_list').each(function(node) {
                var tar = new Y.DD.Drop({
                    node: node,
                    padding: '20 0 20 0'
                });
            }, this);

            //Listen for all drag:start events
            Y.DD.DDM.on('drag:start', this.dragStart, this);
            //Listen for all drag:end events
            Y.DD.DDM.on('drag:end', this.dragEnd, this);
            //Listen for all drag:drag events
            Y.DD.DDM.on('drag:drag', this.dragDrag, this);
            //Listen for all drop:over events
            Y.DD.DDM.on('drop:over', this.dropOver, this);
            //Listen for all drop:hit events
            Y.DD.DDM.on('drop:hit', this.dropHit, this);
        },

        getDragElement: function() {
            var dragelement = Y.Node.create('<span></span>');
            dragelement.addClass('bookmark_handle');
            dragelement.setAttribute('title', M.str.moodle.move);
            var dragicon = Y.Node.create('<img />');
            dragicon.setAttribute('src', M.util.image_url(MOVEICON.pix, MOVEICON.component));
            dragicon.addClass('iconsmall');
            dragicon.setAttribute('alt', M.str.moodle.move);
            dragelement.appendChild(dragicon);
            return dragelement;
        },

        /*
         * Drag-dropping related functions
         */
        dragStart : function(e) {
            //Get our drag object
            var drag = e.target;
            //Set some styles here
            drag.get('node').setStyle('opacity', '.25');
            drag.get('dragNode').set('innerHTML', drag.get('node').get('innerHTML'));
            drag.get('dragNode').setStyles({
                opacity: '.5',
                borderColor: drag.get('node').getStyle('borderColor'),
                backgroundColor: drag.get('node').getStyle('backgroundColor')
            });
        },

        dragEnd : function(e) {
            var drag = e.target;
            //Put our styles back
            drag.get('node').setStyles({
                visibility: '',
                opacity: '1'
            });
        },

        dragDrag : function(e) {
            //Get the last y point
            var y = e.target.lastXY[1];
            //is it greater than the lastY var?
            if (y < this.lastY) {
                //We are going up
                this.goingUp = true;
            } else {
                //We are going down.
                this.goingUp = false;
            }
            //Cache for next check
            this.lastY = y;
        },

        dropOver : function(e) {
            //Get a reference to our drag and drop nodes
            var drag = e.drag.get('node');
            var drop = e.drop.get('node');

            //Are we dropping on a li node?
            if (drop.get('tagName').toLowerCase() === 'li') {
                //Are we not going up?
                if (!this.goingUp) {
                    drop = drop.get('nextSibling');
                }
                //Add the node to this list
                e.drop.get('node').get('parentNode').insertBefore(drag, drop);
                //Resize this nodes shim, so we can drop on it later.
                e.drop.sizeShim();
            } else {
                if (!drop.contains(drag)) {
                    if (this.goingUp) {
                        drop.appendChild(drag);
                    } else {
                        drop.prepend(drag);
                    }
                }
            }
        },

        dropHit : function(e) {
            var drag = e.drag;
            // Get a reference to our drag node
            var dragnode = drag.get('node');

            // Disable dragging and change styles to confirm the change
            drag.removeHandle('.bookmark_handle');
            var handlenode = dragnode.one('.bookmark_handle');
            var iconnode = handlenode.one('img');
            iconnode.setAttribute('src', M.util.image_url(WAITICON.pix, WAITICON.component));
            handlenode.setStyle('cursor', 'auto');

            // Prepare request parameters
            var reg = new RegExp("-(\\d{1,})$");
            var params = {
                courseid : this.get('courseid'),
                moveid : dragnode.getAttribute('id').match(reg)[1],
                categoryid : dragnode.get('parentNode').getAttribute('id').match(reg)[1],
                sesskey : M.cfg.sesskey
            };

            if (dragnode.get('previousSibling')) {
                params.moveafter = dragnode.get('previousSibling').getAttribute('id').match(reg)[1];
            }

            // Do AJAX request
            Y.io(M.cfg.wwwroot+AJAXURL, {
                method:'POST',
                data:  build_querystring(params),
                on: {
                    complete: function(tid, outcome) {
                        try {
                            var object = Y.JSON.parse(outcome.responseText);
                            if (object.error) {
                                return new M.core.ajaxException(object);
                            }
                            // revert our chnages and enable dragging
                            iconnode.setAttribute('src', M.util.image_url(MOVEICON.pix, MOVEICON.component));
                            handlenode.setStyle('cursor', 'move');
                            drag.addHandle('.bookmark_handle');
                        } catch (e) {
                            return new M.core.exception(e);
                        }
                    }
                },
                context:this
            });
        }

    }, {
        NAME : 'block_recommender_service_bookmark-dragdrop',
        ATTRS : {
            courseid : {
                value : null
            }
        }
    });

    M.block_recommender_service_bookmark = M.block_recommender_service_bookmark || {};
    M.block_recommender_service_bookmark.init_dragdrop = function(Y, params) {
        return new DRAGDROP(params);
    }
// because of the hack with the yui module initialisation using js_init_call, the libraries are defined in the view.php
}, '@VERSION@', {});