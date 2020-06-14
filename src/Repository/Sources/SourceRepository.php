<?php

namespace Sourceml\Repository\Sources;

use Doctrine\ORM\EntityRepository;

use Sourceml\Entity\App\User;
use Sourceml\Entity\JQFileUpload\Media;
use Sourceml\Entity\Sources\Source;

class SourceRepository extends EntityRepository {


    /*
        $params = array(

            // filtrer sur un identifiant de source
            // non défini par défaut
            "source" => 2,

            // filtrer sur le type de source
            // parmis 'album', 'track' et 'source'
            // non défini par défaut
            "sourceType" => "track",

            // filtrer sur la licence
            // non défini par défaut
            "licence" => 1,

            // filtrer sur le fait que la source soit ou non une référence
            // parmis true, false et null
            // null par défaut (ne tient pas compte de la réference)
            "isReference" => null,

            // filter sur le user
            // non défini par défaut
            "user" => $this->getUser(),

            // filtrer sur le role du user sur les sources
            // parmis 'contribute', 'edit' et 'admin'
            // pris en compte uniquement si le parametre "user" est defini
            // non défini par défaut
            "userCan" => "edit",

            // filtrer sur l'auteur
            // non défini par défaut
            "author" => 3,

            // filtrer sur la validité du role de l'auteur sur les sources
            // pris en compte uniquement si au moins l'un des parametres
            // "user" ou "author" est defini
            // true par défaut
            "isValid" => true,

            // filtrer sur le role de l'auteur sur les sources
            // parmis 'contribute', 'edit' et 'admin'
            // pris en compte uniquement si le parametre "author" est defini
            // non défini par défaut
            "authorCan" => "edit",

            // filtrer sur les sources d'une composition
            // (les morceaux d'un album ou les sources d'un morceau)
            // non défini par défaut
            "composition" => 1,

            // filtrer sur les dérivation d'une source
            // non défini par défaut
            "derivedFrom" => "http://exemple.com/medias/xml/sources/2.xml",

        )
    */

    public function getSourceQuery($params) {
        $em = $this->getEntityManager();
        $dql = "SELECT s FROM Sourceml\Entity\Sources\Source s";
        $dqlWhere = "";
        $orderBy = array(
            "s.creationDate" => "DESC",
            "s.publicationDate" => "DESC",
        );
        $dqlParameters = array();
        $isReference = isset($params["isReference"]) ? ($params["isReference"] ? true : false) : null;
        if(isset($isReference)) {
            $dqlWhere .=
                ($dqlWhere ? " AND " : " WHERE ")
                ."s.referenceUrl IS".($isReference ? " NOT" : "")." NULL";
        }
        if(isset($params["licence"])) {
            $dqlWhere .=
                ($dqlWhere ? " AND " : " WHERE ")
                ."s.licence = :licence_id";
            $dqlParameters["licence_id"] = $params["licence"];
        }
        if(isset($params["source"])) {
            $dqlWhere .=
                ($dqlWhere ? " AND " : " WHERE ")
                ."s.id = :source_id";
            $dqlParameters["source_id"] = $params["source"];
        }
        if(isset($params["user"]) || isset($params["author"])) {
            $dql .=" JOIN s.authors sa";
            $dql .=" JOIN sa.author a";
            $dqlWhere .=
                ($dqlWhere ? " AND " : " WHERE ")
                ."sa.isValid = :is_valid ";
            $dqlParameters["is_valid"] = (
                isset($params["isValid"])
                    ? ($params["isValid"] ? true : false)
                    : true
            );
        }
        if(
                (!isset($params["author"]) && isset($params["user"]) && isset($params["userCan"]))
            ||  (isset($params["author"]) && isset($params["authorCan"]))
        ) {
            $dql .= " JOIN sa.authorRole ar";
        }
        if(!isset($params["author"]) && isset($params["user"])) {
            $user = $params["user"];
            $dqlWhere .=
                ($dqlWhere ? " AND " : " WHERE ")
                ."a.user = :user_id";
            $dqlParameters["user_id"] = $user->getId();
            if(isset($params["userCan"])) {
                $action = $params["userCan"];
                $role_names = "";
                $dqlWhere .=
                    ($dqlWhere ? " AND " : " WHERE ")
                    ."ar.name IN (:role_names)";
                switch($action) {
                    case "contribute":
                        $role_names = array('contributor', 'editor', 'admin');
                        break;
                    case "edit":
                        $role_names = array('editor', 'admin');
                        break;
                    case "admin":
                        $role_names = array('admin');
                        break;
                }
                $dqlParameters["role_names"] = $role_names;
            }
        }
        if(isset($params["author"])) {
            $dqlWhere .=
                ($dqlWhere ? " AND " : " WHERE ")
                ."a.id = :author_id";
            $dqlParameters["author_id"] = $params["author"];
            if(isset($params["authorCan"])) {
                $action = $params["authorCan"];
                $role_names = "";
                $dqlWhere .=
                    ($dqlWhere ? " AND " : " WHERE ")
                    ."ar.name IN (:role_names)";
                switch($action) {
                    case "contribute":
                        $role_names = array('contributor', 'editor', 'admin');
                        break;
                    case "edit":
                        $role_names = array('editor', 'admin');
                        break;
                    case "admin":
                        $role_names = array('admin');
                        break;
                }
                $dqlParameters["role_names"] = $role_names;
            }
        }
        if(isset($params["sourceType"])) {
            $source_type = $params["sourceType"];
            $dql .= " JOIN s.sourceType st";
            $dqlWhere .=
                ($dqlWhere ? " AND " : " WHERE ")
                ."st.name =:source_type";
            $dqlParameters["source_type"] = $source_type;
        }
        if(isset($params["composition"])) {
            $dql .=" JOIN s.compositions sc";
            $dql .=" JOIN sc.composition c";
            $dqlWhere .=
                ($dqlWhere ? " AND " : " WHERE ")
                ."c.id = :composition";
            $dqlParameters["composition"] = $params["composition"];
            if(!isset($orderBy["sc.position"])) {
                $_orderBy = array("sc.position" => "ASC");
                foreach($orderBy as $orderAttribut => $orderWay) {
                    $_orderBy[$orderAttribut] = $orderWay;
                }
                $orderBy = $_orderBy;
            }
        }
        if(isset($params["derivedFrom"])) {
            $dql .=" JOIN s.derivationSources ds";
            $dqlWhere .=
                ($dqlWhere ? " AND " : " WHERE ")
                ."ds.url = :derived_from_url";
            $dqlParameters["derived_from_url"] = $params["derivedFrom"];
        }
        $dqlOrderBy = "";
        foreach($orderBy as $orderAttribut => $orderWay) {
            $dqlOrderBy .= ($dqlOrderBy ? "," : " ORDER BY ").$orderAttribut." ".$orderWay;
        }
        return $em->createQuery($dql.$dqlWhere.$dqlOrderBy)->setParameters($dqlParameters);
    }

    public function userCan($action, User $user, Source $source) {
        if(
            $this->getSourceQuery(
                array(
                    "userCan" => $action,
                    "user" => $user,
                    "source" => $source->getId()
                )
            )->getResult()
        ) {
            return true;
        }
        return false;
    }

    public function deleteInfo(Source $source, $infoKey) {
        $em = $this->getEntityManager();
        foreach($source->getInfos() as $sourceInfo) {
            if($sourceInfo->getInfoKey() == $infoKey) {
                $em->remove($sourceInfo);
            }
        }
        $em->flush();
    }

    public function getMediaSource(Media $media) {
        $em = $this->getEntityManager();
        $query = $em->createQuery("
            SELECT s FROM Sourceml\Entity\Sources\Source s
            JOIN s.documents sd
            JOIN sd.media m
            Where m.id = :media_id
        ")->setParameters(
            array(
                "media_id" => $media->getId()
            )
        );
        try {
            return $query->getSingleResult();
        } catch(\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

}
