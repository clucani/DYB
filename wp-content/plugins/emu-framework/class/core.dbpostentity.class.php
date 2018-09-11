<?php

class emuDbPostEntity extends emuDbEntity
{
    public $postType = 'post';
    public $taxonomy = 'category';

    public $postTitleRaw;
    public $postContentRaw;
    public $postExcerptRaw;
    public $postName;
    public $categories;
    public $catobjects; //array of objects
    public $catslugs; //array of category slugs
    public $permalink;
    public $post;
    public $post_date;
    public $post_date_gmt;

    public $postStatus = 'pending';

    public function __construct( $dbID = null, $post = null, $db_prefix = null, $db_table = null, $specialFieldTypes = null )
    {
        // Because we want to run it at the end of this constructor instead
        $this->run_init = false;

        parent::__construct( $dbID, $post, $db_prefix, $db_table, $specialFieldTypes );

        $post_id = $this->postID;

        if( $post_id && empty($post) )
        {
            $post = get_post( $post_id );
        }
        else if( is_numeric( $post ) )
        {
            $post = get_post( $post );
        }

        if( $post ) $this->getPostData( $post );

        $this->init();
    }

    public function __get( $member )
    {
        switch( $member )
        {
            case "slug":
                return $this->postName;
                
            case "postTitle":
                return apply_filters( 'the_title', $this->postTitleRaw );

            case "postContent":
                return apply_filters( 'the_content', $this->postContentRaw );

            case "postExcerpt":
                return apply_filters( 'the_excerpt', $this->postExcerptRaw );

            case "postTitleUnfiltered":
                return $this->postTitleRaw;

            case "postContentUnfiltered":
                return $this->postContentRaw;

            case "postExcerptUnfiltered":
                return $this->postExcerptRaw;

            default:
                return parent::__get($member);
        }
    }

    public function __set( $member, $value )
    {
        switch( $member )
        {
            case "postTitle":
            case "postContent":
            case "postExcerpt":
                $raw_version = $member.'Raw';
                $this->$raw_version = $value;
            break;
            case "slug":
                $this->postName = $value;
                break;
            default:
                parent::__set($member, $value);
        }
    }

    public function getPostData( $post )
    {
        $this->post = $post;

        $this->postID = $post->ID;
        $this->postTitle = $post->post_title;
        $this->postContent = $post->post_content;
        $this->postExcerpt = $post->post_excerpt;
        $this->postName = $post->post_name;
        $this->userID = $post->post_author;
		$this->post_date = $post->post_date;
		$this->post_date_gmt = $post->post_date_gmt;

        if( $this->taxonomy )
        {
            $this->categories = get_the_term_list( $post->ID, $this->taxonomy, '', ', ', '' );
			$this->catobjects = get_the_terms( $post->ID, $this->taxonomy );
			if( is_array( $this->catobjects ) )
            {
				foreach($this->catobjects as $c)
                {
					$this->catslugs[] = $c->slug;
				}
			}
		}

        $this->permalink = get_permalink( $post->ID );
    }

    public function savePost()
    {
        $wp_data = array();
		$pt = $this->postTitle; // cant use empty() on a __get(member)  http://www.php.net/manual/en/language.oop5.overloading.php#object.get
        if( empty( $pt ) )
            $this->postTitle = '[empty post title]';

        $wp_data['post_title'] = $this->postTitleRaw;
        $wp_data['post_content'] = $this->postContentRaw;
        $wp_data['post_excerpt'] = $this->postExcerptRaw;
        $wp_data['post_name'] = $this->postName;
        $wp_data['post_type'] = $this->postType;
        $wp_data['post_date'] =  $this->post_date;
        $wp_data['post_date_gmt'] =  $this->post_date_gmt;

        if( $this->postID )
        {
            $wp_data['ID'] = $this->postID;
            wp_update_post( $wp_data );
        }
        else
        {
            // Create the post
            if( !$this->userID )
            {
                global $current_user; get_currentuserinfo();
                $this->userID = $current_user->ID;
            }

            $wp_data['post_author'] = $this->userID;
            $wp_data['post_status'] = $this->postStatus;

            $this->postID = wp_insert_post( $wp_data );
        }

    }

    public function delete()
    {
        $this->deletePost();
        $this->deleteRecord();
    }

    public function deleteRecord()
    {
        parent::delete();
    }

    public function deletePost()
    {
        wp_delete_post( $this->post->ID, true );
    }

    public function save()
    {
        $this->savePost();
        parent::save();
    }

    public function update()
    {
        $this->save();
    }

    public function getPost()
    {
        return $this->post;
    }

    public function getPostID()
    {
        return $this->postID;
    }

    public function setPost($post)
    {
        if( is_numeric( $post ) )
            $post = get_post($post);

        if(is_object( $post ))
            $this->getPostData($post);
        else
            trigger_error( "$post must be numeric (post_id) or object (post)" );
            return null;
    }

    public function setPostStatusPublished()
    {
        $this->postStatus = 'publish';
    }

    public function setPostStatusPending()
    {
        $this->postStatus = 'pending';
    }

    public function saveRecord()
    {
        // Update the db record
        parent::save();
    }

    public function getCustomField( $meta_key )
    {
        return get_post_meta( $this->postID, $meta_key, true );
    }

    public function setCustomField( $meta_key, $meta_value )
    {
        update_post_meta($this->postID, $meta_key, $meta_value);
    }

    public function deleteCustomField( $meta_key )
    {
        delete_post_meta($this->postID, $meta_key);
    }

}
?>