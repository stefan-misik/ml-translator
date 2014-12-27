<?php
include_once("MLtranslator.php");


class MLtag_test implements MLtag
{
    public function translate(
        $translator_obj,
        $tag_name,
        $is_end_tag,
        $attributes,
        $source_pos
    )
    {
        $translator_obj->write("Tag_name: " . $tag_name . "\n");
        $translator_obj->write("Is_end_tag: " . $is_end_tag . "\n");
        $translator_obj->write("Source_pos: " . $source_pos . "\n");
        $translator_obj->write("Attributes: \n");
        $translator_obj->write(var_export($attributes, true));
    }

    public function is_pair_tag(
        $tag_name
    )
    {
        return true;
    }
}

class MLtag_basic implements MLtag
{
    public function translate(
        $translator_obj,
        $tag_name,
        $is_end_tag,
        $attributes,
        $source_pos
    )
    {
        if($is_end_tag)
        {
            $translator_obj->write("</$tag_name>");
        }
        else
        {
            $translator_obj->write("<$tag_name>");
        }
    }

    public function is_pair_tag(
        $tag_name
    )
    {
        return true;
    }
}

class MLtag_url implements MLtag
{
    public function translate(
        $translator_obj,
        $tag_name,
        $is_end_tag,
        $attributes,
        $source_pos
    )
    {
        if($is_end_tag)
        {
            $translator_obj->write("</a>");
        }
        else
        {
            $href = isset($attributes["__tag__"]) ? $attributes["__tag__"] : "";
            $translator_obj->write("<a href=\"$href\">");
        }
    }

    public function is_pair_tag(
        $tag_name
    )
    {
        return true;
    }
}

class MLtag_list implements MLtag
{
    private $item_started = false;

    public function translate(
        $translator_obj,
        $tag_name,
        $is_end_tag,
        $attributes,
        $source_pos
    )
    {
        switch($tag_name)
        {
            case "ul":
            case "ol":
                if(false == $is_end_tag)
                {
                    $translator_obj->write("<$tag_name>");
                }
                else
                {
                    if(true == $this->item_started)
                    {
                        $translator_obj->write("</li>");
                    }
                    $translator_obj->write("</$tag_name>");
                }
                $this->item_started = false;
                break;
            case "*":
                if(true == $this->item_started)
                {
                    $translator_obj->write("</li>");
                }
                $this->item_started = true;
                $translator_obj->write("<li>");
                break;
        }
    }

    public function is_pair_tag(
        $tag_name
    )
    {
        switch($tag_name)
        {
            case "ul":
            case "ol":
                return true;
                break;
            case "*":
                return false;
                break;
        }
    }
}

class MLcode {

    private static $translator;


    public static function translate(
        $code,
        $text_only = false
    )
    {
        if(!isset(MLcode::$translator))
        {
            MLcode::create_translator();
        }

        return MLcode::$translator->translate($code, $text_only);

    }

    private static function create_translator(
    )
    {
        $basic = new MLtag_basic();
        $list = new MLtag_list();


        MLcode::$translator = new MLtranslator(
            array(
                /* Basic tags */
                "b" => $basic,
                "u" => $basic,
                "i" => $basic,
                "br" => $basic,
                "hr" => $basic,
                "p" => $basic,
                /* Lists */
                "ol" => $list,
                "ul" => $list,
                "*" => $list,
                /* Links */
                "url" => new MLtag_url(),
                /* Test */
                "test" => new MLtag_test()),
            array(
                "quote" => "\""));

    }

} 