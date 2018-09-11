<?php

class emuPost
{
    public $postType = 'post';
    public $taxonomy = 'category';
    public $data = array();

    public $postTitleRaw;
    public $postContentRaw;
    public $postExcerptRaw;
    public $categories;
    public $catobjects; //array of objects
    public $catslugs; //array of category slugs
    public $permalink;
    public $post;
    public $post_date;
    public $post_date_gmt;
    public $postID;

    public $postStatus = 'pending';

    public function __construct( $post = null )
    {
        $this->config();

        if( $post ) $this->getPostData( $post );

        $this->init();
    }

    public function config() {}
    public function init() {}

    public function __get( $member )
    {
        switch( $member )
        {
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
                if( !isset( $this->data[ $member ] ) ) return null;

                return $this->data[ $member ];
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

            default:
                $this->data[ $member ] = $value;
        }
    }

    public function getPostData( $post )
    {
        if( is_numeric($post) )
            $post = get_post($post);

        if( !is_object($post) )
            return;

        $this->post = $post;

        $this->postID = $post->ID;
        $this->postTitle = $post->post_title;
        $this->postContent = $post->post_content;
        $this->postExcerpt = $post->post_excerpt;
        $this->userID = $post->post_author;

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

    public function getPostThumbnail($size = null)
    {
        if( $size == 'url' ) return wp_get_attachment_url( get_post_thumbnail_id( $this->postID ) );

        $url = get_the_post_thumbnail( $this->postID, $size, array( 'class' => "$size" ) );

        if( is_ssl() ) $url = preg_replace( '/http:/', 'https:', $url );

        return $url;
    }

    public function savePost()
    {
        $wp_data = array();
		$pt = $this->postTitle;// cant use empty() on a __get(member)  http://www.php.net/manual/en/language.oop5.overloading.php#object.get
        if( empty( $pt ) )
            $this->postTitle = '[empty post title]';

        $wp_data['post_title'] = $this->postTitleRaw;
        $wp_data['post_content'] = $this->postContentRaw;
        $wp_data['post_excerpt'] = $this->postExcerptRaw;
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
    }

    public function deletePost()
    {
        wp_delete_post( $this->post->ID, true );
    }

    public function save()
    {
        $this->savePost();
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