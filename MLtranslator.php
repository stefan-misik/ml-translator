<?php
/**
 * Autthor: Ing. Stefan Misik
 * Date: 29. 10. 2014
 * Time: 14:09
 */

/**
 * Class MLtranslator
 *
 * Markup Language translator
 */
class MLtranslator
{
    /* Input ML configuration */
    private $ml_tag_start       = '[';
    private $ml_tag_end         = ']';
    private $ml_quote           = '\'';
    private $ml_escape          = '\\';

    /* State machine states */
    const TEXT              = 0;    /* < Text only */
    const TAG_IGNORE        = 1;    /* < In text only output */
    const TAG_NAME          = 2;    /* < Tag name */
    const TAG_NAME_S        = 3;    /* < Start tag name */
    const TAG_NAME_E        = 4;    /* < End tag name */
    const ATT_NAME          = 5;    /* < Attribute name */
    const ATT_PRE_EQ        = 6;    /* < Consume white space before = */
    const ATT_VAL           = 7;    /* < Some attribute value will follow */
    const ATT_VAL_1         = 8;    /* < Attribute value, without quotes */
    const ATT_VAL_2         = 9;    /* < Attribute value, inside quotes */
    const ATT_VAL_2_ESC     = 10;   /* < Attribute value, escaped charactwer */

    /* Tag name value attribute name */
    const TAG_VAL_NAME = "__tag__";

    /* Input string reference */
    public $input;

    /* Output string */
    public $output;

    /* Associative array of tags definitions */
    private $tags;

    /* Newline string position */
    private $replace_str_pos = 0;

    public function __construct(
        $tags,
        $config = null
    )
    {
        /* Configure translator */
        if (null != $config)
        {
            if (is_string($config["tag_start"]))
            {
                $this->ml_tag_start = $config["tag_start"];
            }

            if (is_string($config["tag_end"]))
            {
                $this->ml_tag_end = $config["tag_end"];
            }

            if (is_string($config["quote"]))
            {
                $this->ml_quote = $config["quote"];
            }

            if (is_string($config["escape"]))
            {
                $this->ml_escape = $config["escape"];
            }
        }

        /* Store tags definition */
        $this->tags = $tags;
    }

    public function translate
    (
        $ml,
        $text_only = false
    )
    {
        /* Initialize members */
        $this->input = & $ml;
        $this->output = "";

        /* Initialize local variables */
        $input_len = strlen($ml);
        $state = MLtranslator::TEXT;
        $tag_name = "";
        $is_end_tag = false;
        $attributes = array();
        $attribute_name = "";
        $attribute_val = "";
        $tag_completed = false;
        $tag_trace = array();


        /* Main loop */
        for ($pos = 0; $pos < $input_len; $pos++)
        {
            /* Read character */
            $c = $ml[$pos];

            switch($state)
            {
                /* Only Text */
                case MLtranslator::TEXT:
                    if ($c == $this->ml_tag_start)
                    {
                        $state = $text_only ? MLtranslator::TAG_IGNORE : MLtranslator::TAG_NAME;

                        /* Clean Tag name and attributes */
                        $tag_name = "";
                        $attributes = array();
                        $is_end_tag = false;
                    }
                    else
                    {
                        $this->output .= $c;
                    }
                    break;

                /* Ignoring tag */
                case MLtranslator::TAG_IGNORE:
                    if($c == $this->ml_tag_end)
                    {
                        $state = MLtranslator::TEXT;
                    }
                    break;

                /* Inside tag: Tag name */
                case MLtranslator::TAG_NAME:
                    $state = MLtranslator::TAG_NAME_S;
                    if($c == "/")
                    {
                        $state = MLtranslator::TAG_NAME_E;
                        $is_end_tag = true;
                        break;
                    }

                /* Inside tag: Tag name, known position */
                case MLtranslator::TAG_NAME_S:
                case MLtranslator::TAG_NAME_E:
                    if (ctype_space($c))
                    {
                        $state = MLtranslator::ATT_PRE_EQ;

                        $attribute_name = MLtranslator::TAG_VAL_NAME;
                        $attribute_val = "";
                    }
                    elseif ($c == "=")
                    {
                        $state = MLtranslator::ATT_VAL;

                        $attribute_name = MLtranslator::TAG_VAL_NAME;
                        $attribute_val = "";
                    }
                    elseif ($c == $this->ml_tag_end)
                    {
                        $state = MLtranslator::TEXT;

                        /* Set the flag to inform about tag completion */
                        $tag_completed = true;
                    }
                    else
                    {
                        $tag_name .= $c;
                    }
                    break;

                /* Inside Tag: Attribute name */
                case MLtranslator::ATT_NAME:
                    if ($c == $this->ml_tag_end)
                    {
                        $state = MLtranslator::TEXT;

                        if ("" != $attribute_name)
                        {
                            $attributes[$attribute_name] = $attribute_val;
                            $attribute_name = "";
                            $attribute_val = "";
                        }

                        /* Set the flag to inform about tag completion */
                        $tag_completed = true;
                    }
                    elseif (ctype_space($c))
                    {
                        $state = MLtranslator::ATT_PRE_EQ;
                    }
                    elseif ($c == "=")
                    {
                        $state = MLtranslator::ATT_VAL;

                        $attribute_val = "";
                    }
                    else
                    {
                        $attribute_name .= $c;
                    }
                    break;

                /* Inside tag: Consume white space before '=' */
                case MLtranslator::ATT_PRE_EQ:
                    if (ctype_space($c))
                    {
                    }
                    elseif ($c == "=")
                    {
                        $state = MLtranslator::ATT_VAL;
                    }
                    elseif ($c == $this->ml_tag_end)
                    {
                        $state = MLtranslator::TEXT;

                        /* Set the flag to inform about tag completion */
                        $tag_completed = true;
                    }
                    else        /* Another attribute already started */
                    {
                        $state = MLtranslator::ATT_NAME;

                        if ("" != $attribute_name)
                        {
                            $attributes[$attribute_name] = "";
                        }

                        $attribute_name = $c;
                        $attribute_val = "";
                    }
                    break;

                /* Inside tag: Attribute value */
                case MLtranslator::ATT_VAL:
                    $state = MLtranslator::ATT_VAL_1;

                    if ($c == $this->ml_quote)
                    {
                        $state = MLtranslator::ATT_VAL_2;
                        break;
                    }
                    elseif (ctype_space($c))
                    {
                        $state = MLtranslator::ATT_VAL;
                        break;
                    }

                /* Inside Tag: Attribute value without quotes */
                case MLtranslator::ATT_VAL_1:
                    if (ctype_space($c))
                    {
                        $state = MLtranslator::ATT_NAME;
                        if("" != $attribute_name)
                        {
                            $attributes[$attribute_name] = $attribute_val;
                        }

                        $attribute_name = "";
                        $attribute_val = "";
                    }
                    elseif ($c == $this->ml_tag_end)
                    {
                        $state = MLtranslator::TEXT;

                        if ("" != $attribute_name)
                        {
                            $attributes[$attribute_name] = $attribute_val;
                        }

                        /* Set the flag to inform about tag completion */
                        $tag_completed = true;
                    }
                    else
                    {
                        $attribute_val .= $c;
                    }
                    break;

                /* Inside Tag: Attribute value inside quotes */
                case MLtranslator::ATT_VAL_2:
                    if ($c == $this->ml_quote)
                    {
                        $state = MLtranslator::ATT_NAME;

                        if("" != $attribute_name)
                        {
                            $attributes[$attribute_name] = $attribute_val;
                        }

                        $attribute_name = "";
                        $attribute_val = "";
                    }
                    elseif ($c == $this->ml_escape)
                    {
                        $state = MLtranslator::ATT_VAL_2_ESC;
                    }
                    else
                    {
                        $attribute_val .= $c;
                    }
                    break;

                /* Inside Tag: Attribute value inside quotes, escaped character */
                case MLtranslator::ATT_VAL_2_ESC:
                    $attribute_val .= $c;
                    $state = MLtranslator::ATT_VAL_2;
                    break;

                default:
                    /* Something gone really wrong */
                    throw new Exception("Bad state-machine state.");
                    break;
            }

            /* Check flag if whole tag was parsed */
            if (true == $tag_completed)
            {


                if (true == array_key_exists($tag_name, $this->tags))
                {
                    $tag_obj = $this->tags[$tag_name];

                    if(true == $tag_obj->is_pair_tag($tag_name))
                    {
                        if(false == $is_end_tag)
                        {
                            array_push($tag_trace, $tag_name);
                        }
                        else
                        {
                            $tag = array_pop($tag_trace);
                            if($tag != $tag_name)
                            {
                                throw new Exception("Code structure error in tag $tag_name at pos: $pos. Expected end of $tag first.");
                            }
                        }
                    }


                    $tag_obj->translate(
                        $this,
                        $tag_name,
                        $is_end_tag,
                        $attributes,
                        $pos
                    );
                }

                /* Reset tag completion flag */
                $tag_completed = false;
            }
        }

        $ret = $this->output;
        /* Cleanup */
        $this->output = null;
        $this->input = null;

        return $ret;
    }

    public function write(
        $string
    )
    {
        $this->output .= $string;
    }
}


interface MLtag
{
    public function translate(
        $translator_obj,
        $tag_name,
        $is_end_tag,
        $attributes,
        $source_pos
    );

    public function is_pair_tag(
        $tag_name
    );
}