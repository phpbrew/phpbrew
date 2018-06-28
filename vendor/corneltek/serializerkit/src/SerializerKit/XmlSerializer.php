<?php
namespace SerializerKit;
use DOMDocument;
use DOMNode;
use DOMText;

class XmlSerializer
{
    function encode($array) 
    {
        $dom = new DOMDocument('1.0');
        $dom->formatOutput = true;
        $dom->encoding = 'utf-8';
        $node = $dom->createElement('data');
        $this->toNodes($dom,$node,$array);
        $dom->appendChild( $node );
        return $dom->saveXml();
    }

    function toNodes($dom,$node,$array,$nodeName = null)
    {
        foreach( $array as $k => $v ) {
            $kNode = null;
            if( $nodeName ) {
                $kNode = $dom->createElement($nodeName);
            } else {
                $kNode = $dom->createElement($k);
            }

            if( is_string($v) ) {
                $cdata = $dom->createCDATASection( $v );
                $kNode->appendChild( $cdata );
                $node->appendChild( $kNode );
            }
            elseif( is_integer($v) || is_float($v) ) {
                $text = $dom->createTextNode( $v );
                $kNode->appendChild( $text );
                $node->appendChild( $kNode );
            }
            elseif( is_array($v) && ! isset($v[0]) ) {
                $this->toNodes($dom,$kNode,$v);
                $node->appendChild( $kNode );
            }
            elseif( is_array($v) && isset($v[0]) ) {
                $this->toNodes($dom,$node,$v,$k);
            }
        }
    }

    function decode($xml) 
    {
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->loadXml( $xml );
        $dataTag = $dom->getElementsByTagName('data')->item(0);
        $data = $this->toArray( $dom, $dataTag );
        return $data['data'];
    }


    public function toArray(DOMDocument $dom, DOMNode $oDomNode = null)
    {
        // return empty array if dom is blank
        if (is_null($oDomNode) && !$dom->hasChildNodes()) {
            return array();
        }
        $oDomNode = (is_null($oDomNode)) ? $dom->documentElement : $oDomNode;
        if (!$oDomNode->hasChildNodes()) {
            $mResult = $oDomNode->nodeValue;
        } else {
            $mResult = array();
            foreach ($oDomNode->childNodes as $oChildNode) {
                // how many of these child nodes do we have?
                // this will give us a clue as to what the result structure should be
                $oChildNodeList = $oDomNode->getElementsByTagName($oChildNode->nodeName);  
                $iChildCount = 0;
                // there are x number of childs in this node that have the same tag name
                // however, we are only interested in the # of siblings with the same tag name
                foreach ($oChildNodeList as $oNode) {
                    if ($oNode->parentNode->isSameNode($oChildNode->parentNode)) {
                        $iChildCount++;
                    }
                }

                $mValue = $this->toArray($dom, $oChildNode);
                $sKey   = ($oChildNode->nodeName{0} == '#') ? 0 : $oChildNode->nodeName;
                $mValue = is_array($mValue) ? $mValue[$oChildNode->nodeName] : $mValue;
                // how many of thse child nodes do we have?
                if ($iChildCount > 1) {  // more than 1 child - make numeric array
                    $mResult[$sKey][] = $mValue;
                } else {
                    $mResult[$sKey] = $mValue;
                }
            }
            // if the child is <foo>bar</foo>, the result will be array(bar)
            // make the result just 'bar'
            if (count($mResult) == 1 && isset($mResult[0]) && !is_array($mResult[0])) {
                $mResult = $mResult[0];
            }
        }
        // get our attributes if we have any
        $arAttributes = array();
        if ($oDomNode->hasAttributes()) {
            foreach ($oDomNode->attributes as $sAttrName=>$oAttrNode) {
                // retain namespace prefixes
                $arAttributes["@{$oAttrNode->nodeName}"] = $oAttrNode->nodeValue;
            }
        }
        // check for namespace attribute - Namespaces will not show up in the attributes list
        if ($oDomNode instanceof DOMElement && $oDomNode->getAttribute('xmlns')) {
            $arAttributes["@xmlns"] = $oDomNode->getAttribute('xmlns');
        }
        if (count($arAttributes)) {
            if (!is_array($mResult)) {
                $mResult = (trim($mResult)) ? array($mResult) : array();
            }
            $mResult = array_merge($mResult, $arAttributes);
        }
        $arResult = array($oDomNode->nodeName=>$mResult);
        return $arResult;
    }

}



